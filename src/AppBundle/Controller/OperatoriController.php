<?php

namespace AppBundle\Controller;


use AppBundle\Entity\Allegato;
use AppBundle\Entity\Ente;
use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\Pratica;

use AppBundle\Form\Base\MessageType;
use AppBundle\Form\Operatore\Base\PraticaOperatoreFlow;
use AppBundle\Logging\LogConstants;
use AppBundle\Services\InstanceService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;


/**
 * Class OperatoriController
 * @Route("/operatori")
 */
class OperatoriController extends Controller
{
  /**
   * @Route("/",name="operatori_index")
   * @Template()
   * @return array
   */
  public function indexAction()
  {
    $praticheRepo = $this->getDoctrine()->getRepository('AppBundle:Pratica');
    $user = $this->getUser();
    /** @var Ente $ente */
    $ente = $user->getEnte();

    $praticheMie = $praticheRepo->findPraticheAssignedToOperatore($user);
    $praticheLibere = $praticheRepo->findPraticheUnAssignedByEnte($ente);
    $praticheConcluse = $praticheRepo->findPraticheCompletedByOperatore($user);
    $praticheEnte = $praticheRepo->findPraticheByEnte($ente);

    return array(
      'pratiche_mie' => $praticheMie,
      'pratiche_libere' => $praticheLibere,
      'pratiche_concluse' => $praticheConcluse,
      'pratiche_ente' => $praticheEnte,
      'user' => $this->getUser(),
    );
  }

  /**
   * @Route("/usage",name="operatori_usage")
   * @Template()
   * @return array
   */
  public function usageAction()
  {
    $repo = $this->getDoctrine()->getRepository(Pratica::class);
    $pratiche = $repo->findSubmittedPraticheByEnte($this->get('ocsdc.instance_service')->getCurrentInstance());

    $serviziRepository = $this->getDoctrine()->getRepository('AppBundle:Servizio');
    $servizi = $serviziRepository->findBy(
      [
        'status' => [1]
      ]
    );

    $count = array_reduce($pratiche, function ($acc, $el) {
      $year = (new \DateTime())->setTimestamp($el->getSubmissionTime())->format('Y');
      try {
        $acc[$year]++;
      } catch (\Exception $e) {
        $acc[$year] = 1;
      }

      return $acc;
    }, []);

    return array(
      'servizi' => count($servizi),
      'pratiche' => $count,
      'user' => $this->getUser()
    );
  }

  /**
   * @Route("/{pratica}/protocollo", name="operatori_pratiche_show_protocolli")
   * @Template("@App/Operatori/showProtocolli.html.twig")
   * @param Pratica $pratica
   *
   * @return array
   * @throws \Exception
   */
  public function showProtocolliAction(Request $request, Pratica $pratica)
  {
    $user = $this->getUser();
    $this->checkUserCanAccessPratica($this->getUser(), $pratica);
    $resumeURI = $request->getUri();
    $threads = $this->createThreadElementsForOperatoreAndPratica($this->getUser(), $pratica);

    $allegati = [];
    foreach ($pratica->getNumeriProtocollo() as $protocollo) {
      $allegato = $this->getDoctrine()->getRepository('AppBundle:Allegato')->find($protocollo->id);
      if ($allegato instanceof Allegato) {
        $allegati[] = [
          'allegato' => $allegato,
          'tipo' => (new \ReflectionClass(get_class($allegato)))->getShortName(),
          'protocollo' => $protocollo->protocollo
        ];
      }
    }

    return [
      'pratica' => $pratica,
      'allegati' => $allegati,
      'user' => $user,
      'threads' => $threads,
    ];
  }


  /**
   * @param InstanceService $instanceService
   *
   * @Route("/parametri-protocollo", name="operatori_impostazioni_protocollo_list")
   * @Template("@App/Operatori/impostazioniProtocollo.html.twig")
   * @return array
   */
  public function impostazioniProtocolloListAction()
  {
    return array('parameters' => $this->get('ocsdc.instance_service')->getCurrentInstance()->getProtocolloParameters());
  }

  /**
   * @Route("/{pratica}/autoassign",name="operatori_autoassing_pratica")
   * @param Pratica $pratica
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   * @throws \Exception
   */
  public function autoAssignPraticaAction(Pratica $pratica)
  {
    if ($pratica->getOperatore() !== null) {
      throw new BadRequestHttpException("Pratica {$pratica->getId()} already assigned to {$pratica->getOperatore()->getFullName()}");
    }

    if ($pratica->getNumeroProtocollo() === null) {
      throw new BadRequestHttpException("Pratica {$pratica->getId()} does not have yet a protocol number");
    }

    $pratica->setOperatore($this->getUser());
    $this->get('ocsdc.pratica_status_service')->setNewStatus($pratica, Pratica::STATUS_PENDING);

    $this->get('logger')->info(
      LogConstants::PRATICA_ASSIGNED,
      [
        'pratica' => $pratica->getId(),
        'user' => $pratica->getUser()->getId(),
      ]
    );

    return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica]);
  }

  /**
   * @Route("/{pratica}/detail",name="operatori_show_pratica")
   * @Template()
   *
   * @return array
   */
  public function showPraticaAction(Pratica $pratica, Request $request)
  {
    $this->checkUserCanAccessPratica($this->getUser(), $pratica);

    $form = $this->setupCommentForm()->handleRequest($request);

    if ($form->isSubmitted()) {
      $commento = $form->getData();
      $pratica->addCommento($commento);
      $this->getDoctrine()->getManager()->flush();

      $this->get('logger')->info(
        LogConstants::PRATICA_COMMENTED,
        [
          'pratica' => $pratica->getId(),
          'user' => $pratica->getUser()->getId(),
        ]
      );

      return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica]);
    }

    $threads = $this->createThreadElementsForOperatoreAndPratica($this->getUser(), $pratica);

    return [
      'form' => $form->createView(),
      'pratica' => $pratica,
      'user' => $this->getUser(),
      'threads' => $threads,
    ];
  }

  /**
   * @Route("/{pratica}/elabora",name="operatori_elabora_pratica")
   * @Template()
   * @param Pratica $pratica
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function elaboraPraticaAction(Pratica $pratica)
  {
    if ($pratica->getStatus() == Pratica::STATUS_COMPLETE || $pratica->getStatus() == Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE) {
      return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica]);
    }

    $this->checkUserCanAccessPratica($this->getUser(), $pratica);
    $user = $this->getUser();

    $praticaFlowService = null;
    $praticaFlowServiceName = $pratica->getServizio()->getPraticaFlowOperatoreServiceName();

    if ($praticaFlowServiceName) {
      /** @var PraticaOperatoreFlow $praticaFlowService */
      $praticaFlowService = $this->get($praticaFlowServiceName);
    } else {
      // Default pratica flow
      $praticaFlowService = $this->get('ocsdc.form.flow.standardoperatore');
    }

    $praticaFlowService->setInstanceKey($user->getId());

    $praticaFlowService->bind($pratica);

    if ($pratica->getInstanceId() == null) {
      $pratica->setInstanceId($praticaFlowService->getInstanceId());
    }

    $form = $praticaFlowService->createForm();
    if ($praticaFlowService->isValid($form)) {

      $praticaFlowService->saveCurrentStepData($form);
      $pratica->setLastCompiledStep($praticaFlowService->getCurrentStepNumber());

      if ($praticaFlowService->nextStep()) {
        $this->getDoctrine()->getManager()->flush();
        $form = $praticaFlowService->createForm();
      } else {

        $this->completePraticaFlow($pratica, $praticaFlowService->hasUploadAllegati());

        $praticaFlowService->getDataManager()->drop($praticaFlowService);
        $praticaFlowService->reset();

        return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica]);
      }
    }

    return [
      'form' => $form->createView(),
      'pratica' => $praticaFlowService->getFormData(),
      'flow' => $praticaFlowService,
      'user' => $user,
    ];
  }


  /**
   * @Route("/list",name="operatori_list_by_ente")
   * @Security("has_role('ROLE_OPERATORE_ADMIN')")
   * @Template()
   * @param Request $request
   * @return array
   */
  public function listOperatoriByEnteAction(Request $request)
  {
    $operatoreRepo = $this->getDoctrine()->getRepository('AppBundle:OperatoreUser');
    $operatori = $operatoreRepo->findBy(
      [
        'ente' => $this->getUser()->getEnte(),
      ]
    );
    return array(
      'operatori' => $operatori,
      'user' => $this->getUser(),
    );
  }

  /**
   * @Route("/detail/{operatore}",name="operatori_detail")
   * @Security("has_role('ROLE_OPERATORE_ADMIN')")
   * @Template()
   * @param Request $request
   * @param OperatoreUser $operatore
   * @return array
   */
  public function detailOperatoreAction(Request $request, OperatoreUser $operatore)
  {
    $this->checkUserCanAccessOperatore($this->getUser(), $operatore);
    $form = $this->setupOperatoreForm($operatore)->handleRequest($request);

    if ($form->isSubmitted()) {
      $data = $form->getData();
      //$this->storeOperatoreData($operatore->getId(), $data, $this->get('logger'));
      $operatore->setAmbito($data['ambito']);
      $this->getDoctrine()->getManager()->persist($operatore);
      try {
        $this->getDoctrine()->getManager()->flush();
        $this->get('logger')->info(LogConstants::OPERATORE_ADMIN_HAS_CHANGED_OPERATORE_AMBITO, ['operatore_admin' => $this->getUser()->getId(), 'operatore' => $operatore->getId()]);
      } catch (\Exception $e) {
        $this->get('logger')->error($e->getMessage());
      }
      return $this->redirectToRoute('operatori_detail', ['operatore' => $operatore->getId()]);
    }

    return array(
      'operatore' => $operatore,
      'form' => $form->createView(),
      'user' => $this->getUser(),
    );
  }

  /**
   * @Route("/logout", name="logout")
   */
  public function logoutAction()
  {
  }

  /**
   * @param OperatoreUser $operatore
   * @return \Symfony\Component\Form\FormInterface
   */
  private function setupOperatoreForm(OperatoreUser $operatore)
  {
    $formBuilder = $this->createFormBuilder()
      ->add('ambito', TextType::class,
        ['label' => false, 'data' => $operatore->getAmbito(), 'required' => false]
      )
      ->add('save', SubmitType::class,
        ['label' => $this->get('translator')->trans('operatori.profile.salva_modifiche')]
      );
    $form = $formBuilder->getForm();
    return $form;
  }

  /**
   * @return FormInterface
   */
  private function setupCommentForm()
  {
    $translator = $this->get('translator');
    $data = array();
    $formBuilder = $this->createFormBuilder($data)
      ->add('text', TextareaType::class, [
        'label' => false,
        'required' => true,
        'attr' => [
          'rows' => '5',
          'class' => 'form-control input-inline',
        ],
      ])
      ->add('createdAt', HiddenType::class, ['data' => time()])
      ->add('creator', HiddenType::class, [
        'data' => $this->getUser()->getFullName(),
      ])
      ->add('save', SubmitType::class, [
        'label' => $translator->trans('operatori.aggiungi_commento'),
        'attr' => [
          'class' => 'btn btn-info',
        ],
      ]);
    $form = $formBuilder->getForm();

    return $form;
  }

  /**
   * @param OperatoreUser $user
   * @param Pratica $pratica
   */
  private function checkUserCanAccessPratica(OperatoreUser $user, Pratica $pratica)
  {
    $operatore = $pratica->getOperatore();
    if (!$operatore instanceof OperatoreUser || $operatore->getId() !== $user->getId()) {
      throw new UnauthorizedHttpException("User can not read pratica {$pratica->getId()}");
    }
  }

  /**
   * @param OperatoreUser $user
   * @param OperatoreUser $operatore
   */
  private function checkUserCanAccessOperatore(OperatoreUser $user, OperatoreUser $operatore)
  {
    if ($user->getEnte() != $operatore->getEnte()) {
      throw new UnauthorizedHttpException("User can not read operatore {$operatore->getId()}");
    }
  }

  /**
   * @param Pratica $pratica
   */
  private function completePraticaFlow(Pratica $pratica, $hasNewAllegati = false)
  {

    if ($pratica->getRispostaOperatore() == null) {
      $signedResponse = $this->get('ocsdc.modulo_pdf_builder')->createSignedResponseForPratica($pratica);
      $pratica->addRispostaOperatore($signedResponse);
    }


    if ($pratica->getEsito()) {
      $this->get('ocsdc.pratica_status_service')->setNewStatus($pratica, Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE);

      $this->get('logger')->info(
        LogConstants::PRATICA_APPROVED,
        [
          'pratica' => $pratica->getId(),
          'user' => $pratica->getUser()->getId(),
        ]
      );
    } else {
      $this->get('ocsdc.pratica_status_service')->setNewStatus($pratica, Pratica::STATUS_CANCELLED_WAITALLEGATIOPERATORE);

      $this->get('logger')->info(
        LogConstants::PRATICA_CANCELLED,
        [
          'pratica' => $pratica->getId(),
          'user' => $pratica->getUser()->getId(),
        ]
      );
    }
  }

  /**
   * @param OperatoreUser $operatore
   * @param Pratica $pratica
   *
   * @return array
   */
  private function createThreadElementsForOperatoreAndPratica(OperatoreUser $operatore, Pratica $pratica)
  {
    $messagesAdapterService = $this->get('ocsdc.messages_adapter');
    $threadId = $pratica->getUser()->getId() . '~' . $operatore->getId();
    $form = $this->createForm(
      MessageType::class,
      ['thread_id' => $threadId, 'sender_id' => $operatore->getId()],
      [
        'action' => $this->get('router')->generate('messages_controller_enqueue_for_operatore', ['threadId' => $threadId]),
        'method' => 'PUT',
      ]
    );

    $threads[] = [
      'threadId' => $threadId,
      'title' => $pratica->getUser()->getFullName(),
      'messages' => $messagesAdapterService->getDecoratedMessagesForThread($threadId, $operatore),
      'form' => $form->createView(),
    ];

    return $threads;
  }

}
