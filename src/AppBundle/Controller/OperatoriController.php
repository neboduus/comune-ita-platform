<?php

namespace AppBundle\Controller;


use AppBundle\Dto\Application;
use AppBundle\Entity\Allegato;
use AppBundle\Entity\CPSUser;
use AppBundle\Entity\FormIO;
use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\PraticaRepository;
use AppBundle\Entity\Servizio;
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
use Symfony\Component\HttpFoundation\JsonResponse;
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
    /** @var PraticaRepository $praticaRepository */
    $praticaRepository = $this->getDoctrine()->getRepository(Pratica::class);

    $servizi = $this->getDoctrine()->getRepository(Servizio::class)->findBy(
      [
        'id' => $praticaRepository->getServizioIdList(Pratica::STATUS_SUBMITTED),
      ]
    );

    $stati = [];
    foreach ($praticaRepository->getStateList(Pratica::STATUS_SUBMITTED) as $state) {
      $state['name'] = $this->get('translator')->trans($state['name']);
      $stati[] = $state;
    }

    return array(
      'servizi' => $servizi,
      'stati' => $stati,
      'user' => $this->getUser(),
    );
  }

  /**
   * @todo megiare questa logica in ApplicationsAPIController
   * @Route("/pratiche",name="operatori_index_json")
   */
  public function indexJsonAction(Request $request)
  {
    /** @var PraticaRepository $praticaRepository */
    $praticaRepository = $this->getDoctrine()->getRepository(Pratica::class);
    /** @var OperatoreUser $user */
    $user = $this->getUser();

    $limit = intval($request->get('limit', 10));
    $offset = intval($request->get('offset', 0));

    $servizioId = $request->get('servizio', false);
    $parameters = [
      'servizio' => $servizioId,
      'stato' => $request->get('stato', false),
      'workflow' => $request->get('workflow', false),
      'query_field' => $request->get('query_field', false),
      'query' => $request->get('query', false),
    ];

    try {
      $count = $praticaRepository->countPraticheByOperatore($user, $parameters);
      /** @var Pratica[] $data */
      $data = $praticaRepository->findPraticheByOperatore($user, $parameters, $limit, $offset);
    }catch (\Throwable $e){
      $count = 0;
      $data = [];
      $result['meta']['error'] = true; //$e->getMessage();
    }

    $schema = null;
    $result = [];
    $result['meta']['schema'] = false;
    if ($servizioId && $count > 0){
      $servizio = $this->getDoctrine()->getManager()->getRepository(Servizio::class)->findOneBy(['id' => $servizioId]);
      if ($servizio instanceof Servizio){
        $schema = $this->container->get('formio.factory')->createFromFormId($servizio->getFormIoId());
        if ($schema->hasComponents()) {
          $result['meta']['schema'] = $schema->getComponents();
        }
      }
    }

    $result['meta']['count'] = $count;
    $currentParameters = $parameters;
    $currentParameters['offset'] = $offset;
    $currentParameters['limit'] = $limit;
    $result['meta']['parameter'] = $currentParameters;
    $result['links']['self'] = $this->generateUrl('operatori_index_json', $currentParameters);
    $result['links']['prev'] = null;
    $result['links']['next'] = null;
    if ($offset != 0) {
      $prevParameters = $parameters;
      $prevParameters['offset'] = $offset - $limit;
      $prevParameters['limit'] = $limit;
      $result['links']['prev'] = $this->generateUrl('operatori_index_json', $prevParameters);
    }
    if ($offset + $limit < $count) {
      $nextParameters = $parameters;
      $nextParameters['offset'] = $offset + $limit;
      $nextParameters['limit'] = $limit;
      $result['links']['next'] = $this->generateUrl('operatori_index_json', $nextParameters);
    }

    $serializer = $this->container->get('jms_serializer');
    foreach ($data as $s) {
      $application = Application::fromEntity($s);
      $applicationArray = json_decode($serializer->serialize($application, 'json'), true);
      $applicationArray['can_autoassign'] = $s->getOperatore() == null;
      $applicationArray['is_protocollo_required'] = $s->getServizio()->isProtocolRequired();
      $applicationArray['is_payment_required'] = $s->getServizio()->isPaymentRequired();
      $applicantUser = $s->getUser();
      $codiceFiscale = $applicantUser instanceof CPSUser ? $applicantUser->getCodiceFiscale() : '';
      $codiceFiscaleParts = explode('-', $codiceFiscale);
      $applicationArray['codice_fiscale'] = array_shift($codiceFiscaleParts);

      try{
        $this->checkUserCanAccessPratica($user, $s);
        $applicationArray['can_read'] = true;
      }catch (UnauthorizedHttpException $e){
        $applicationArray['can_read'] = false;
      }

      if (isset($schema) && $schema->hasComponents() && $s instanceof FormIO){
        $applicationArray['data'] = $schema->getDataBuilder()->setDataFromArray($s->getDematerializedForms()['data'])->toFullFilledFlatArray();
      }

      $result['data'][] = $applicationArray;
    }

    $request->setRequestFormat('json');
    return new JsonResponse(json_encode($result), 200, [], true);
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

    if ($pratica->getServizio()->isProtocolRequired() && $pratica->getNumeroProtocollo() === null) {
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

    if ($pratica->getType() == 'form_io') {
      $relatedPratica = key_exists('related_applications', $pratica->getDematerializedForms()['data']) ? $pratica->getDematerializedForms()['data']['related_applications'] : null;
      if ($relatedPratica)
        $relatedPratica = $this->getDoctrine()->getRepository('AppBundle:Pratica')->find($relatedPratica);
    } else {
      $relatedPratica = null;
    }

    /** @var CPSUser $user */
    $user = $pratica->getUser();
    $others = $this->getDoctrine()->getRepository('AppBundle:Pratica')->findBy(['user'=>$user]);
    $form = $this->setupCommentForm()->handleRequest($request);

    if ($form->isSubmitted()) {

      $commento = $form->getData();
      $pratica->addCommento($commento);
      $this->getDoctrine()->getManager()->flush();


      $this->get('logger')->info(
        LogConstants::PRATICA_COMMENTED,
        [
          'pratica' => $pratica->getId(),
          'user' => $pratica->getUser()->getId()
        ]
      );

      return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica]);
    }

    $threads = $this->createThreadElementsForOperatoreAndPratica($this->getUser(), $pratica);

    $userFullname = $user->getFullName();
    if ($pratica->getType() == Pratica::TYPE_FORMIO) {
      /** @var AppBundle\FormIO\Schema $schema */
      $schema = $this->container->get('formio.factory')->createFromFormId($pratica->getServizio()->getFormIoId());
      $data = $schema->getDataBuilder()->setDataFromArray($pratica->getDematerializedForms()['data'])->toFullFilledFlatArray();
      if ( isset( $data['applicant.fiscal_code.fiscal_code'] ) ) {
        $userFullname .= ' ( ' . $data['applicant.fiscal_code.fiscal_code'] . ' )';
      }

    } else {
      $userFullname .= ' ( ' . $pratica->getUser()->getCodiceFiscale() . ' )';
    }

    return [
      'others' => $others,
      'relatedPratica' => $relatedPratica,
      'form' => $form->createView(),
      'pratica' => $pratica,
      'user' => $this->getUser(),
      'threads' => $threads,
      'user_full_name' => $userFullname,
      'formserver_url' => $this->getParameter('formserver_public_url')
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
