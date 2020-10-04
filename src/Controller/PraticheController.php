<?php

namespace App\Controller;

use App\Entity\Allegato;
use App\Entity\CPSUser;
use App\Entity\Ente;
use App\Entity\GiscomPratica;
use App\Entity\Message;
use App\Entity\Nota;
use App\Entity\OperatoreUser;
use App\Entity\Pratica;
use App\Entity\PraticaRepository;
use App\Entity\Servizio;
use App\Entity\User;
use App\Form\Base\MessageType;
use App\Form\Base\PraticaFlow;
use App\Handlers\Servizio\ForbiddenAccessException;
use App\Handlers\Servizio\ServizioHandlerRegistry;
use App\Logging\LogConstants;
use App\Model\CallToAction;
use App\Services\InstanceService;
use App\Services\ModuloPdfBuilderService;
use App\Services\PraticaStatusService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class PraticheController
 *
 * @package AppBundle\Controller
 * @Route("/pratiche")
 */
class PraticheController extends Controller
{

  const ENTE_SLUG_QUERY_PARAMETER = 'ente';

  /**
   * @Route("/", name="pratiche")
   * @Template()
   *
   * @return array
   */
  public function indexAction()
  {
    /** @var CPSUser $user */
    $user = $this->getUser();
    /** @var PraticaRepository $repo */
    $repo = $this->getDoctrine()->getRepository('AppBundle:Pratica');
    $pratiche = $repo->findBy(
      array('user' => $user),
      array('status' => 'DESC')
    );

    $praticheDraft = $repo->findDraftPraticaForUser($user);
    $pratichePending = $repo->findPendingPraticaForUser($user);
    //$praticheProcessing = $repo->findProcessingPraticaForUser($user);
    $praticheCompleted = $repo->findCompletePraticaForUser($user);
    $praticheCancelled = $repo->findCancelledPraticaForUser($user);
    $praticheDraftForIntegration = $repo->findDraftForIntegrationPraticaForUser($user);
    $praticheRelated = $repo->findRelatedPraticaForUser($user);
    $praticheWithdrawn = $repo->findWithdrawnPraticaForUser($user);


    return [
      'user' => $user,
      'pratiche' => $pratiche,
      'title' => 'lista_pratiche',
      'tab_pratiche' => array(
        'draft' => $praticheDraft,
        'pending' => $pratichePending,
        //'processing' => $praticheProcessing,
        'completed' => $praticheCompleted,
        'integration' => $praticheDraftForIntegration,
        'related' => $praticheRelated,
        'cancelled' => $praticheCancelled,
        'withdrawn' => $praticheWithdrawn
      ),
    ];
  }

  /**
   * @Route("/{servizio}/new", name="pratiche_new")
   * @ParamConverter("servizio", class="AppBundle:Servizio", options={"mapping": {"servizio": "slug"}})
   *
   * @param Request $request
   * @param Servizio $servizio
   *
   * @param InstanceService $instanceService
   * @return Response
   */
  public function newAction(Request $request, Servizio $servizio, InstanceService $instanceService)
  {
    $handler = $this->get(ServizioHandlerRegistry::class)->getByName($servizio->getHandler());

    $ente = $instanceService->getCurrentInstance();

    if (!$ente instanceof Ente) {
      $this->get('logger')->info(LogConstants::PRATICA_WRONG_ENTE_REQUESTED, ['headers' => $request->headers]);
      throw new \InvalidArgumentException(LogConstants::PRATICA_WRONG_ENTE_REQUESTED);
    }

    try {
      $handler->canAccess($servizio, $ente);
    } catch (ForbiddenAccessException $e) {
      $this->addFlash('warning', $this->get('translator')->trans($e->getMessage(), $e->getParameters()));

      return $this->redirectToRoute('servizi_list');
    }

    try {

      return $handler->execute($servizio, $ente);
    } catch (\Exception $e) {
      $this->get('logger')->error($e->getMessage(), ['servizio' => $servizio->getSlug()]);

      return $this->render(
        '@App/Servizi/serviziFeedback.html.twig',
        array(
          'servizio' => $servizio,
          'status' => 'danger',
          'message' => $handler->getErrorMessage(),
          'message_detail' => $e->getMessage(),
        )
      );
    }
  }

  /**
   * @Route("/{servizio}/draft", name="pratiche_list_draft")
   * @ParamConverter("servizio", class="AppBundle:Servizio", options={"mapping": {"servizio": "slug"}})
   * @Template()
   * @param Servizio $servizio
   *
   * @return array
   */
  public function listDraftByServiceAction(Servizio $servizio)
  {
    $user = $this->getUser();
    $repo = $this->getDoctrine()->getRepository('AppBundle:Pratica');
    $pratiche = $repo->findBy(
      array(
        'user' => $user,
        'servizio' => $servizio,
        'status' => [
          Pratica::STATUS_DRAFT,
          Pratica::STATUS_DRAFT_FOR_INTEGRATION,
        ],
      ),
      array('creationTime' => 'ASC')
    );

    return [
      'user' => $user,
      'pratiche' => $pratiche,
      'title' => 'bozze_servizio',
      'msg' => array(
        'type' => 'warning',
        'text' => 'msg_bozze_servizio',
      ),
    ];
  }

  /**
   * @Route("/compila/{pratica}", name="pratiche_compila")
   * @ParamConverter("pratica", class="AppBundle:Pratica")
   * @Template()
   * @param Pratica $pratica
   *
   * @return array|RedirectResponse
   */
  public function compilaAction(Request $request, Pratica $pratica)
  {
    $em = $this->getDoctrine()->getManager();
    if ($pratica->getStatus() !== Pratica::STATUS_DRAFT_FOR_INTEGRATION
      && $pratica->getStatus() !== Pratica::STATUS_DRAFT
      && $pratica->getStatus() !== Pratica::STATUS_PAYMENT_PENDING) {
      return $this->redirectToRoute(
        'pratiche_show',
        ['pratica' => $pratica->getId()]
      );
    }

    $handler = $this->get(ServizioHandlerRegistry::class)->getByName($pratica->getServizio()->getHandler());
    try {
      $handler->canAccess($pratica->getServizio(), $pratica->getEnte());
    } catch (ForbiddenAccessException $e) {
      $this->addFlash('warning', $this->get('translator')->trans($e->getMessage(), $e->getParameters()));

      return $this->redirectToRoute('pratiche');
    }

    $user = $this->getUser();
    $this->checkUserCanAccessPratica($pratica, $user);

    /** @var PraticaFlow $praticaFlowService */
    $praticaFlowService = $this->get($pratica->getServizio()->getPraticaFlowServiceName());

    if ($pratica->getServizio()->isPaymentRequired()) {
      $praticaFlowService->setPaymentRequired(true);
    }

    $praticaFlowService->setInstanceKey($user->getId());

    $praticaFlowService->bind($pratica);

    if ($pratica->getInstanceId() == null) {
      $pratica->setInstanceId($praticaFlowService->getInstanceId());
    }
    $resumeURI = $praticaFlowService->getResumeUrl($request);
    //$thread = $this->createThreadElementsForUserAndPratica($pratica, $user, $resumeURI);

    $form = $praticaFlowService->createForm();
    if ($praticaFlowService->isValid($form)) {

      $currentStep = $praticaFlowService->getCurrentStepNumber();

      $praticaFlowService->saveCurrentStepData($form);
      $pratica->setLastCompiledStep($currentStep);

      if ($praticaFlowService->nextStep()) {

        $em->flush();
        $form = $praticaFlowService->createForm();

        $resumeURI = $praticaFlowService->getResumeUrl($request);
        //$thread = $this->createThreadElementsForUserAndPratica($pratica, $user, $resumeURI);

      } else {

        $praticaFlowService->onFlowCompleted($pratica);

        $this->get('logger')->info(
          LogConstants::PRATICA_UPDATED,
          ['id' => $pratica->getId(), 'pratica' => $pratica]
        );

        // $this->addFlash('feedback', $this->get('translator')->trans('pratica_ricevuta'));

        $praticaFlowService->getDataManager()->drop($praticaFlowService);
        $praticaFlowService->reset();

        return $this->redirectToRoute(
          'pratiche_show',
          ['pratica' => $pratica->getId()]
        );
      }
    }

    return [
      'form' => $form->createView(),
      'pratica' => $praticaFlowService->getFormData(),
      'flow' => $praticaFlowService,
      'formserver_url' => $this->getParameter('formserver_public_url'),
      'user' => $user,
      //'threads' => $thread,
    ];
  }

  private function checkUserCanAccessPratica(Pratica $pratica, CPSUser $user)
  {
    $praticaUser = $pratica->getUser();
    $isTheOwner = $praticaUser->getId() === $user->getId();
    $cfs = $pratica->getRelatedCFs();
    if (!is_array($cfs)) {
      $cfs = [$cfs];
    }
    $isRelated = in_array($user->getCodiceFiscale(), $cfs);


    if (!$isTheOwner && !$isRelated) {
      throw new UnauthorizedHttpException("User can not read pratica {$pratica->getId()}");
    }
  }

  /**
   * @param Pratica $pratica
   * @param CPSUser $user
   * @return bool
   */
  private function userCanWithdrawPratica(Pratica $pratica, User $user)
  {
    return $pratica->getStatus() == Pratica::STATUS_SUBMITTED && empty($pratica->getPaymentData()) && !$pratica->getServizio()->isProtocolRequired() && $pratica->getUser()->getId() == $user->getId();
  }

  /**
   * @Route("/{pratica}", name="pratiche_show")
   * @ParamConverter("pratica", class="AppBundle:Pratica")
   * @Template()
   * @param Pratica $pratica
   *
   * @return array
   */
  public function showAction(Request $request, Pratica $pratica)
  {
    /** @var CPSUser $user */
    $user = $this->getUser();
    $this->checkUserCanAccessPratica($pratica, $user);
    $resumeURI = $request->getUri();

    $canCompile = ($pratica->getStatus() == Pratica::STATUS_DRAFT || $pratica->getStatus() == Pratica::STATUS_DRAFT_FOR_INTEGRATION)
      && $pratica->getUser()->getId() == $user->getId();
    if ($canCompile) {
      $handler = $this->get(ServizioHandlerRegistry::class)->getByName($pratica->getServizio()->getHandler());
      try {
        $handler->canAccess($pratica->getServizio(), $pratica->getEnte());
      } catch (ForbiddenAccessException $e) {
        $canCompile = false;
      }
    }


    $result = [
      'pratica' => $pratica,
      'user' => $user,
      'formserver_url' => $this->getParameter('formserver_public_url'),
      'can_compile' => $canCompile,
      'can_withdraw' => $this->userCanWithdrawPratica($pratica, $user)
      //'threads' => $thread,
    ];

    if ($pratica instanceof GiscomPratica) {
      $allegati = [];
      $attachments = $pratica->getAllegati();
      if (count($attachments) > 0) {

        /** @var Allegato $a */
        foreach ($attachments as $a) {
          $allegati[$a->getId()] = [
            'numero_protocollo' => $a->getNumeroProtocollo(),
            'id_documento_protocollo'  => $a->getIdDocumentoProtocollo(),
            'description'  => $a->getDescription()
          ];
        }
      }
      $result['allegati'] = $allegati;
    }

    return $result;
  }

  /**
   * @Route("/{pratica}/detail", name="pratica_show_detail")
   * @ParamConverter("pratica", class="AppBundle:Pratica")
   * @Template()
   * @param Pratica $pratica
   *
   * @return RedirectResponse
   */
  public function detailAction(Request $request, Pratica $pratica)
  {
    $translator = $this->get('translator');

    if (!$this->get('flagception.manager.feature_manager')->isActive('feature_application_detail')) {
      return $this->redirectToRoute('pratiche_show', ['pratica' => $pratica]);
    }

    /** @var CPSUser $user */
    $user = $this->getUser();
    $this->checkUserCanAccessPratica($pratica, $user);
    $tab = $request->query->get('tab');

    $attachments = $this->getDoctrine()->getRepository('AppBundle:Pratica')->getMessageAttachments(['visibility'=> Message::VISIBILITY_APPLICANT, 'author' => $pratica->getUser()->getId()], $pratica);

    $canCompile = ($pratica->getStatus() == Pratica::STATUS_DRAFT || $pratica->getStatus() == Pratica::STATUS_DRAFT_FOR_INTEGRATION)
      && $pratica->getUser()->getId() == $user->getId();
    if ($canCompile) {
      $handler = $this->get(ServizioHandlerRegistry::class)->getByName($pratica->getServizio()->getHandler());
      try {
        $handler->canAccess($pratica->getServizio(), $pratica->getEnte());
      } catch (ForbiddenAccessException $e) {
        $canCompile = false;
      }
    }

    $message = new Message();
    $message->setApplication($pratica);
    $message->setAuthor($user);
    $messageForm = $this->createForm('AppBundle\Form\ApplicationMessageType', $message);
    $messageForm->handleRequest($request);

    if ($messageForm->isSubmitted() && $messageForm->isValid()) {
      /** @var Message $message */
      $message = $messageForm->getData();

      $callToActions = [
        ['label'=>'view', 'link'=>$this->generateUrl('operatori_show_pratica', ['pratica' => $pratica, 'tab'=>'note'], UrlGeneratorInterface::ABSOLUTE_URL)],
        ['label'=>'reply', 'link'=>$this->generateUrl('operatori_show_pratica', ['pratica' => $pratica, 'tab'=>'note'], UrlGeneratorInterface::ABSOLUTE_URL)],
      ];

      $message->setProtocolRequired(false);
      $message->setVisibility(Message::VISIBILITY_APPLICANT);
      $message->setCallToAction($callToActions);

      $em = $this->getDoctrine()->getManager();
      $em->persist($message);
      $em->flush();

      $this->get('logger')->info(
        LogConstants::PRATICA_COMMENTED,
        [
          'pratica' => $pratica->getId(),
          'user' => $pratica->getUser()->getId()
        ]
      );

      // Todo: rendere asincrono l'invio delle email
      if ($pratica->getOperatore()) {
        $instance = $this->get('ocsdc.instance_service')->getCurrentInstance();
        /** @var OperatoreUser $userReceiver */
        $userReceiver = $message->getApplication()->getOperatore();
        $subject = $translator->trans('pratica.messaggi.oggetto', ['%pratica%' => $pratica]);
        $mess = $translator->trans('pratica.messaggi.messaggio', [
          '%message%' => $message->getMessage(),
          '%link%' => $this->get('router')->generate('track_message', ['id'=>$message->getId()], UrlGeneratorInterface::ABSOLUTE_URL) . '?id='. $message->getId()]);
        $this->get('ocsdc.mailer')->dispatchMail($user->getEmail(), $user->getFullName(),$userReceiver->getEmail(), $userReceiver->getFullName(), $mess, $subject, $instance, $message->getCallToAction());

        $message->setSentAt(time());
        $em->persist($message);
        $em->flush();
      }


      return $this->redirectToRoute('pratica_show_detail', ['pratica' => $pratica, 'tab'=>'note']);
    }

    $repository = $this->getDoctrine()->getRepository('AppBundle:Pratica');
    $praticheRecenti = $repository->findRecentlySubmittedPraticheByUser($pratica, $user, 5);

    $result = [
      'pratiche_recenti' => $praticheRecenti,
      'applications_in_folder' => $repository->getApplicationsInFolder($pratica),
      'messageAttachments' => $attachments,
      'messageForm' => $messageForm->createView(),
      'tab' => $tab,
      'pratica' => $pratica,
      'user' => $user,
      'formserver_url' => $this->getParameter('formserver_public_url'),
      'can_compile' => $canCompile,
      'can_withdraw' => $this->userCanWithdrawPratica($pratica, $user)
      //'threads' => $thread,
    ];

    if ($pratica instanceof GiscomPratica) {
      $allegati = [];
      $attachments = $pratica->getAllegati();
      if (count($attachments) > 0) {

        /** @var Allegato $a */
        foreach ($attachments as $a) {
          $allegati[$a->getId()] = [
            'numero_protocollo' => $a->getNumeroProtocollo(),
            'id_documento_protocollo'  => $a->getIdDocumentoProtocollo(),
            'description'  => $a->getDescription()
          ];
        }
      }
      $result['allegati'] = $allegati;
    }

    return $result;
  }

  /**
   * @Route("/{pratica}/withdraw", name="pratiche_withdraw")
   * @ParamConverter("pratica", class="AppBundle:Pratica")
   * @param Request $request
   * @param Pratica $pratica
   *
   * @param PraticaStatusService $praticaStatusService
   * @param ModuloPdfBuilderService $pdfBuilderService
   * @return array|RedirectResponse
   * @throws \Exception
   */
  public function withdrawAction(Request $request, Pratica $pratica, PraticaStatusService $praticaStatusService, ModuloPdfBuilderService $pdfBuilderService)
  {
    /** @var CPSUser $user */
    $user = $this->getUser();
    if ($this->userCanWithdrawPratica($pratica, $user)) {

      $withdrawAttachment = $pdfBuilderService->createWithdrawForPratica($pratica);
      $pratica->addAllegato($withdrawAttachment);
      $praticaStatusService->setNewStatus(
        $pratica,
        Pratica::STATUS_WITHDRAW
      );
    }

    return $this->redirectToRoute(
      'pratiche_show', ['pratica' => $pratica->getId()]
    );
  }

  /**
   * @Route("/{pratica}/payment-callback", name="pratiche_payment_callback")
   * @ParamConverter("pratica", class="AppBundle:Pratica")
   * @param Request $request
   * @param Pratica $pratica
   *
   * @param PraticaStatusService $praticaStatusService
   * @return array|RedirectResponse
   * @throws \Exception
   */
  public function paymentCallbackAction(Request $request, Pratica $pratica, PraticaStatusService $praticaStatusService)
  {
    $user = $this->getUser();
    $this->checkUserCanAccessPratica($pratica, $user);
    $outcome = $request->get('esito');

    if ($outcome == 'OK') {
      $praticaStatusService->setNewStatus(
        $pratica,
        Pratica::STATUS_PAYMENT_OUTCOME_PENDING
      );
    }

    return $this->redirectToRoute(
      'pratiche_show',
      [
        'pratica' => $pratica,
      ]
    );

  }

  /**
   * @Route("/{pratica}/pdf", name="pratiche_show_pdf")
   * @ParamConverter("pratica", class="AppBundle:Pratica")
   * @Template()
   * @param Request $request
   * @param Pratica $pratica
   *
   * @param ModuloPdfBuilderService $pdfBuilderService
   * @return BinaryFileResponse
   */
  public function showPdfAction(Request $request, Pratica $pratica, ModuloPdfBuilderService $pdfBuilderService)
  {
    $user = $this->getUser();
    $this->checkUserCanAccessPratica($pratica, $user);
    $allegato = $pdfBuilderService->showForPratica($pratica);

    $fileName = $allegato->getOriginalFilename();
    if (substr($fileName, -3) != $allegato->getFile()->getExtension()) {
      $fileName .= '.'.$allegato->getFile()->getExtension();
    }

    return new BinaryFileResponse(
      $allegato->getFile()->getPath().'/'.$allegato->getFile()->getFilename(),
      200,
      [
        'Content-type' => 'application/octet-stream',
        'Content-Disposition' => sprintf('attachment; filename="%s"', $fileName),
      ]
    );
  }

  /**
   * @Route("/{pratica}/delete", name="pratiche_delete")
   * @ParamConverter("pratica", class="AppBundle:Pratica")
   * @Template()
   * @param Pratica $pratica
   *
   * @return array
   */
  public function deleteAction(Request $request, Pratica $pratica)
  {
    $user = $this->getUser();
    $this->checkUserCanAccessPratica($pratica, $user);
    if ($pratica->getStatus() != Pratica::STATUS_DRAFT) {
      throw new UnauthorizedHttpException("Pratica can't be deleted, not in draft status");
    }

    $em = $this->getDoctrine()->getManager();
    $em->remove($pratica);
    $em->flush();


    return $this->redirectToRoute('pratiche');
  }

  /**
   * @Route("/{pratica}/protocollo", name="pratiche_show_protocolli")
   * @ParamConverter("pratica", class="AppBundle:Pratica")
   * @Template()
   * @param Pratica $pratica
   *
   * @return array
   */
  public function showProtocolliAction(Request $request, Pratica $pratica)
  {
    $user = $this->getUser();
    $this->checkUserCanAccessPratica($pratica, $user);
    $resumeURI = $request->getUri();
    //$thread = $this->createThreadElementsForUserAndPratica($pratica, $user, $resumeURI);

    $allegati = [];
    foreach ($pratica->getNumeriProtocollo() as $protocollo) {
      $allegato = $this->getDoctrine()->getRepository('AppBundle:Allegato')->find($protocollo->id);
      if ($allegato instanceof Allegato) {
        $allegati[] = [
          'allegato' => $allegato,
          'tipo' => (new \ReflectionClass(get_class($allegato)))->getShortName(),
          'protocollo' => $protocollo->protocollo,
        ];
      }
    }

    return [
      'pratica' => $pratica,
      'allegati' => $allegati,
      'user' => $user,
      //'threads' => $thread,
    ];
  }

  /**
   * @Route("/formio/validate/{servizio}", name="formio_validate")
   * @ParamConverter("servizio", class="AppBundle:Servizio", options={"mapping": {"servizio": "slug"}})
   *
   * @param Request $request
   * @param Servizio $servizio
   *
   * @return JsonResponse
   */
  public function formioValidateAction(Request $request, Servizio $servizio)
  {
    $validator = $this->get('formio.expression_validator');

    $errors = $validator->validateData(
      $servizio->getFormIoId(),
      $request->getContent(),
      $servizio->getPostSubmitValidationExpression(),
      $servizio->getPostSubmitValidationMessage()
    );

    $response = ['status' => 'OK', 'errors' => null];
    if (!empty($errors)){
      $response = ['status' => 'KO', 'errors' => $errors];
    }

    return JsonResponse::create($response, Response::HTTP_OK);
  }

  /**
   * @param Pratica $pratica
   * @param $user
   * @return array
   */
  private function createThreadElementsForUserAndPratica(Pratica $pratica, User $user, $returnURL)
  {

    if ($pratica->getEnte()) {
      $messagesAdapterService = $this->get('ocsdc.messages_adapter');
      //FIXME: this should be the Capofila Ente (the first in the array of the Erogatore's ones)
      $ente = $pratica->getEnte();
      $servizio = $pratica->getServizio();
      $userThread = $messagesAdapterService->getThreadsForUserEnteAndService($user, $ente, $servizio);
      if (!$userThread) {
        return null;
      }

      $threadId = $userThread[0]->threadId;
      $threadForm = $this->createForm(
        MessageType::class,
        [
          'thread_id' => $threadId,
          'sender_id' => $user->getId(),
          'return_url' => $returnURL,
        ],
        [
          'action' => $this->get('router')->generate('messages_controller_enqueue_for_user', ['threadId' => $threadId]),
          'method' => 'PUT',
        ]
      );

      $thread = [
        'threadId' => $threadId,
        'title' => $userThread[0]->title,
        'messages' => $messagesAdapterService->getDecoratedMessagesForThread($threadId, $user),
        'form' => $threadForm->createView(),
      ];

      return [$thread];
    }

    return null;
  }
}
