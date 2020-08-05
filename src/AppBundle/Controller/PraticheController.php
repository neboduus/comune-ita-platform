<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\CPSUser;
use AppBundle\Entity\Ente;
use AppBundle\Entity\GiscomPratica;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\PraticaRepository;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\User;
use AppBundle\Form\Base\MessageType;
use AppBundle\Form\Base\PraticaFlow;
use AppBundle\Handlers\Servizio\ForbiddenAccessException;
use AppBundle\Handlers\Servizio\ServizioHandlerRegistry;
use AppBundle\Logging\LogConstants;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

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


    return [
      'user' => $user,
      'pratiche' => $pratiche,
      'title' => 'lista_pratiche',
      'tab_pratiche' => array(
        'draft' => $praticheDraft,
        'pending' => $pratichePending,
        //'processing' => $praticheProcessing,
        'completed' => $praticheCompleted,
        'cancelled' => $praticheCancelled,
        'integration' => $praticheDraftForIntegration,
        'related' => $praticheRelated,
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
   * @return Response
   */
  public function newAction(Request $request, Servizio $servizio)
  {
    $handler = $this->get(ServizioHandlerRegistry::class)->getByName($servizio->getHandler());

    $ente = $this->getDoctrine()
      ->getRepository('AppBundle:Ente')
      ->findOneBy(
        [
          'slug' => $this->container->hasParameter('prefix') ? $this->container->getParameter(
            'prefix'
          ) : $request->query->get(self::ENTE_SLUG_QUERY_PARAMETER, null),
        ]
      );

    if (!$ente instanceof Ente) {
      $this->get('logger')->info(
        LogConstants::PRATICA_WRONG_ENTE_REQUESTED,
        ['headers' => $request->headers]
      );

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
    //$thread = $this->createThreadElementsForUserAndPratica($pratica, $user, $resumeURI);

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
      //'threads' => $thread,
    ];

    if ($pratica instanceof GiscomPratica) {
      $allegati = [];
      $attachments = $pratica->getAllegati();
      if (count($attachments) > 0) {

        /** @var Allegato $a */
        foreach ($attachments as $a) {
          $allegati[$a->getId()] = $a->getNumeroProtocollo();
        }
      }
      $result['allegati'] = $allegati;
    }

    return $result;
  }

  /**
   * @Route("/{pratica}/payment-callback", name="pratiche_payment_callback")
   * @ParamConverter("pratica", class="AppBundle:Pratica")
   * @param Pratica $pratica
   *
   * @return array|RedirectResponse
   */
  public function paymentCallbackAction(Request $request, Pratica $pratica)
  {
    $user = $this->getUser();
    $this->checkUserCanAccessPratica($pratica, $user);
    $outcome = $request->get('esito');

    if ($outcome == 'OK') {
      $this->container->get('ocsdc.pratica_status_service')->setNewStatus(
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
   * @param Pratica $pratica
   *
   * @return array
   */
  public function showPdfAction(Request $request, Pratica $pratica)
  {
    $user = $this->getUser();
    $this->checkUserCanAccessPratica($pratica, $user);
    $allegato = $this->container->get('ocsdc.modulo_pdf_builder')->showForPratica($pratica);

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
