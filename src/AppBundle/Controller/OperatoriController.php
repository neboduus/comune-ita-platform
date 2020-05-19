<?php

namespace AppBundle\Controller;


use AppBundle\Dto\Application;
use AppBundle\Entity\Allegato;
use AppBundle\Entity\CPSUser;
use AppBundle\Entity\DematerializedFormPratica;
use AppBundle\Entity\FormIO;
use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\PraticaRepository;
use AppBundle\Entity\Servizio;
use AppBundle\Form\Base\MessageType;
use AppBundle\Form\Operatore\Base\PraticaOperatoreFlow;
use AppBundle\FormIO\Schema;
use AppBundle\Logging\LogConstants;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
    /** @var OperatoreUser $user */
    $user = $this->getUser();

    /** @var PraticaRepository $praticaRepository */
    $praticaRepository = $this->getDoctrine()->getRepository(Pratica::class);

    $servizi = $this->getDoctrine()->getRepository(Servizio::class)->findBy(
      [
        'id' => $praticaRepository->getServizioIdListByOperatore($user, PraticaRepository::OPERATORI_LOWER_STATE),
      ]
    );

    $stati = [];
    foreach ($praticaRepository->getStateListByOperatore($user, PraticaRepository::OPERATORI_LOWER_STATE) as $state) {
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
   * @param Request $request
   * @return JsonResponse
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
      'sort' => $request->get('sort', 'submissionTime'),
      'order' => $request->get('order', 'asc'),
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
      $minimunStatusForAssign = $s->getServizio()->isProtocolRequired() ? Pratica::STATUS_REGISTERED : Pratica::STATUS_SUBMITTED;
      $applicationArray['can_autoassign'] = $s->getOperatore() == null && $s->getStatus() >= $minimunStatusForAssign;
      $applicationArray['is_protocollo_required'] = $s->getServizio()->isProtocolRequired();
      $applicationArray['is_payment_required'] = $s->getServizio()->isPaymentRequired();
      $applicantUser = $s->getUser();
      $codiceFiscale = $applicantUser instanceof CPSUser ? $applicantUser->getCodiceFiscale() : '';
      $codiceFiscaleParts = explode('-', $codiceFiscale);
      $applicationArray['codice_fiscale'] = array_shift($codiceFiscaleParts);
      $applicationArray['operator_name'] = $s->getOperatore() ? $s->getOperatore()->getFullName() : null;

      try{
        $this->checkUserCanAccessPratica($user, $s);
        $applicationArray['can_read'] = true;
      }catch (UnauthorizedHttpException $e){
        $applicationArray['can_read'] = false;
      }

      if (isset($schema) && $schema->hasComponents() && $s instanceof FormIO){
        $dematerialized = $s->getDematerializedForms();
        if (isset($dematerialized['data'])){
          $applicationArray['data'] = $schema->getDataBuilder()->setDataFromArray($dematerialized['data'])->toFullFilledFlatArray();
        }else{
          $applicationArray['data'] = array_fill_keys($schema->getComponentsColumns('name'), '');
        }
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
    //$repo = $this->getDoctrine()->getRepository(Pratica::class);
    //$pratiche = $repo->findSubmittedPraticheByEnte($this->get('ocsdc.instance_service')->getCurrentInstance());
    $serviziRepository = $this->getDoctrine()->getRepository('AppBundle:Servizio');
    $servizi = $serviziRepository->findBy(
      [
        'status' => Servizio::STATUS_AVAILABLE
      ]
    );

    $timeZone = date_default_timezone_get();
    $sql = "SELECT COUNT(p.id), date_trunc('year', TO_TIMESTAMP(p.submission_time) AT TIME ZONE '". $timeZone. "') AS tslot
            FROM pratica AS p WHERE p.status > 1000 GROUP BY tslot ORDER BY tslot ASC";

    /** @var EntityManager $em */
    $em = $this->getDoctrine()->getManager();
    try {
      $stmt = $em->getConnection()->prepare($sql);
      $stmt->execute();
      $result = $stmt->fetchAll(FetchMode::ASSOCIATIVE);
    }catch (DBALException $e){
      $this->get('logger')->error($e->getMessage());
      $result = [];
    }

    $statusServices = $this->populateSelectStatusServicesPratiche();
    return array(
      'servizi' => $servizi,
      'pratiche' => $result,
      'user' => $this->getUser(),
      'statusServices' => $statusServices
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
  public function showProtocolliAction(Pratica $pratica)
  {
    /** @var OperatoreUser $user */
    $user = $this->getUser();
    $this->checkUserCanAccessPratica($user, $pratica);
    $threads = $this->createThreadElementsForOperatoreAndPratica($user, $pratica);

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
    /** @var OperatoreUser $user */
    $user = $this->getUser();
    try {
      if ($pratica->getOperatore() !== null) {
        throw new BadRequestHttpException(
          "La pratica è già assegnata a {$pratica->getOperatore()->getFullName()}"
        );
      }

      if ($pratica->getServizio()->isProtocolRequired() && $pratica->getNumeroProtocollo() === null) {
        throw new BadRequestHttpException("La pratica non ha ancora un numero di protocollo");
      }

      $pratica->setOperatore($user);
      $this->get('ocsdc.pratica_status_service')->setNewStatus($pratica, Pratica::STATUS_PENDING);

      $this->get('logger')->info(
        LogConstants::PRATICA_ASSIGNED,
        [
          'pratica' => $pratica->getId(),
          'user' => $pratica->getUser()->getId(),
        ]
      );
    }catch (\Exception $e){
      $this->addFlash('error', $e->getMessage());
    }

    return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica]);
  }

  /**
   * @Route("/{pratica}/detail",name="operatori_show_pratica")
   * @Template()
   * @param Pratica|DematerializedFormPratica $pratica
   * @param Request $request
   * @return array|RedirectResponse
   */
  public function showPraticaAction(Pratica $pratica, Request $request)
  {
    /** @var OperatoreUser $user */
    $user = $this->getUser();
    $this->checkUserCanAccessPratica($user, $pratica);

    /** @var CPSUser $applicant */
    $applicant = $pratica->getUser();

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

    $modalForm = $this->createForm('AppBundle\Form\Operatore\Base\ApprovaORigettaType')->handleRequest($request);
    if ($modalForm->isSubmitted()) {
      $pratica->setEsito($modalForm->getData()['esito']);
      if (isset($modalForm->getData()['motivazioneEsito'])) {
        $pratica->setMotivazioneEsito($modalForm->getData()['motivazioneEsito']);
      }

      try {
        $this->completePraticaFlow($pratica);
      }catch (\Exception $e){
        $this->addFlash('error', $e->getMessage());
      }
      return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica]);
    }

    $threads = $this->createThreadElementsForOperatoreAndPratica($user, $pratica);
    /** @var PraticaRepository $repository */
    $repository = $this->getDoctrine()->getRepository('AppBundle:Pratica');
    $praticheRecenti = $repository->findRecentlySubmittedPraticheByUser($pratica, $applicant, 5);

    $praticaCorrelata = null;
    $fiscalCode = '';
    if ($pratica->getType() == Pratica::TYPE_FORMIO) {
      /** @var Schema $schema */
      $schema = $this->container->get('formio.factory')->createFromFormId($pratica->getServizio()->getFormIoId());
      $data = $schema->getDataBuilder()->setDataFromArray($pratica->getDematerializedForms()['data'])->toFullFilledFlatArray();
      if ( isset( $data['applicant.fiscal_code.fiscal_code'] ) ) {
        $fiscalCode = $data['applicant.fiscal_code.fiscal_code'];
      }
      if ( isset( $data['related_applications'] ) ) {
        try {
          $praticaCorrelata = $this->getDoctrine()->getRepository('AppBundle:Pratica')->find(trim($data['related_applications']));
        } catch (\Exception $exception) {
          $praticaCorrelata = null;
        }
      }
    } else {
      $fiscalCode = $applicant->getCodiceFiscale() ;
    }

    return [
      'pratiche_recenti' => $praticheRecenti,
      'pratica_correlata' => $praticaCorrelata,
      'form' => $form->createView(),
      'modalForm' => $modalForm->createView(),
      'pratica' => $pratica,
      'user' => $this->getUser(),
      'threads' => $threads,
      'fiscal_code' => $fiscalCode,
      'formserver_url' => $this->getParameter('formserver_public_url'),
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

    /** @var OperatoreUser $user */
    $user = $this->getUser();
    $this->checkUserCanAccessPratica($user, $pratica);

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

        try {
          $this->completePraticaFlow($pratica);
        }catch (\Exception $e){
          $this->addFlash('error', $e->getMessage());
        }

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
   * @return array
   */
  public function listOperatoriByEnteAction()
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
   * @return array|RedirectResponse
   */
  public function detailOperatoreAction(Request $request, OperatoreUser $operatore)
  {
    /** @var OperatoreUser $user */
    $user = $this->getUser();
    $this->checkUserCanAccessOperatore($user, $operatore);
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
          'class' => 'btn btn-primary',
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
    $isEnabled = in_array($pratica->getServizio()->getId(), $user->getServiziAbilitati()->toArray());
    if (!$isEnabled) {
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
   * @throws \Exception
   */
  private function completePraticaFlow(Pratica $pratica)
  {
    if ($pratica->getStatus() == Pratica::STATUS_COMPLETE
      || $pratica->getStatus() == Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE
      || $pratica->getStatus() == Pratica::STATUS_CANCELLED
      || $pratica->getStatus() == Pratica::STATUS_CANCELLED_WAITALLEGATIOPERATORE)
    {
      throw new BadRequestHttpException('La pratica è già stata elaborata');
    }

    if ($pratica->getRispostaOperatore() == null) {
      $signedResponse = $this->get('ocsdc.modulo_pdf_builder')->createSignedResponseForPratica($pratica);
      $pratica->addRispostaOperatore($signedResponse);
    }

    $protocolloIsRequired = $pratica->getServizio()->isProtocolRequired();

    if ($pratica->getEsito()) {

      if ($protocolloIsRequired) {
        $this->get('ocsdc.pratica_status_service')->setNewStatus(
          $pratica,
          Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE
        );
      }else{
        $this->get('ocsdc.pratica_status_service')->setNewStatus(
          $pratica,
          Pratica::STATUS_COMPLETE
        );
      }

      $this->get('logger')->info(
        LogConstants::PRATICA_APPROVED,
        [
          'pratica' => $pratica->getId(),
          'user' => $pratica->getUser()->getId(),
        ]
      );
    } else {
      if ($protocolloIsRequired) {
        $this->get('ocsdc.pratica_status_service')->setNewStatus(
          $pratica,
          Pratica::STATUS_CANCELLED_WAITALLEGATIOPERATORE
        );
      }else{
        $this->get('ocsdc.pratica_status_service')->setNewStatus(
          $pratica,
          Pratica::STATUS_CANCELLED
        );
      }

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


  private function populateSelectStatusServicesPratiche()
  {
    /** @var EntityManager $em */
    $em = $this->getDoctrine()->getManager();

    //Servizi, pratiche  delle select di filtraggio
    $serviziPratiche = $em->createQueryBuilder()
      ->select('s.name', 's.slug')
      ->from('AppBundle:Pratica', 'p')
      ->innerJoin('AppBundle:Servizio', 's', 'WITH', 's.id = p.servizio')
      ->distinct()
      ->getQuery()
      ->getResult();

    $sql = "SELECT DISTINCT(status) as status
            FROM pratica WHERE status > 1000 ORDER BY status ASC";
    try {
      $em = $this->getDoctrine()->getManager();
      $stmt = $em->getConnection()->prepare($sql);
      $stmt->execute();
      $result = $stmt->fetchAll();
    }catch (DBALException $e){
      $this->get('logger')->error($e->getMessage());
      $result = [];
    }

    $status = [];
    foreach ($result as $valore) {
      $status[] = array(
        "status" => $valore['status'],
        "name" => $this->getStatusAsString($valore['status']));
    }

    return array(
      'statiPratiche' => $status,
      'serviziPratiche' => $serviziPratiche,
    );
  }

  /**
   * @Route("/usage/metriche", name="metriche")
   * @Method("GET")
   * @param Request $request
   * @return Response
   */
  public function metricheAction(Request $request )
  {
    $status = $request->get('status');
    $services = $request->get('services');
    $time = $request->get('time');

    if ($time <= 180) {
      $timeSlot = "minute";
      $timeDiff = "- " . $time . " minutes";
    } elseif ($time <= 1440 ) {
      $timeSlot = "hour";
      $timeDiff = "- " . ($time / 60) . " hours";
    } else {
      $timeSlot = "day";
      $timeDiff = "- " . ($time / 60 / 24) . " days";
    }

    $timeZone = date_default_timezone_get();

    $calculateInterval = date('Y-m-d H:i:s', strtotime($timeDiff));

    $where = " WHERE p.status > 1000 AND TO_TIMESTAMP(p.submission_time) AT TIME ZONE '".$timeZone."' >= '". $calculateInterval . "'";

    if($services && $services != 'all'){
      $where .= " AND s.slug =" ."'".$services."'";
    }

    if($status && $status != 'all'){
      $where .= " AND p.status =" ."'".$status."'";
    }

    $sql = "SELECT COUNT(p.id), date_trunc('". $timeSlot ."', TO_TIMESTAMP(p.submission_time) AT TIME ZONE '".$timeZone."') AS tslot, s.name
            FROM pratica AS p LEFT JOIN servizio AS s ON p.servizio_id = s.id" .
            $where .
            " GROUP BY s.name, tslot ORDER BY tslot ASC";

    /** @var EntityManager $em */
    $em = $this->getDoctrine()->getManager();
    try {
      $stmt = $em->getConnection()->prepare($sql);
      //$stmt->bindValue(1, $calculateInterval);
      $stmt->execute();
      $result = $stmt->fetchAll(FetchMode::ASSOCIATIVE);
    }catch (DBALException $e){
      $this->get('logger')->error($e->getMessage());
      $result = [];
    }

    $categories = $series = $data = array();

    foreach ($result as $r) {
      if (!in_array($r['tslot'], $categories)) {
        $categories []= $r['tslot'];
      }
      $series[$r['name']][$r['tslot']] = $r['count'];
    }

    foreach ($series as $k => $v) {
      $temp = [];
      $temp['name'] = $k;
      foreach ($categories as $c) {
        if (isset($v[$c])) {
          $temp['data'][] = $v[$c];
        } else {
          $temp['data'][] = 0;
        }
      }
      $data['series'][]=$temp;
    }

    $data['categories'] = $categories;
    $data['query'] = $sql;
    return new Response(json_encode($data), 200);


    /*
    $calculateInterval = date('Y-m-d H:i:s', strtotime($time));
    $sql = "WITH list_dates as (SELECT count(*) n_pratiche, s.name,  date_trunc('day', TO_TIMESTAMP(p.creation_time)::date) as days
            FROM pratica as p
            INNER JOIN servizio as s ON s.id = p.servizio_id
            WHERE TO_TIMESTAMP(p.creation_time) >= '". $calculateInterval ."'" . $filterServices . $filterStatus . "
            group by s.name, days) select name, array_agg(n_pratiche),array_agg(days) as d from list_dates group by name";

    $stmt = $em->getConnection()->prepare($sql);
    //$stmt->bindValue(1, $calculateInterval);
    $stmt->execute();
    $result = $stmt->fetchAll(FetchMode::ASSOCIATIVE);
    $series= [];
    $categories= [];
    $data= [];
    if(count($result) > 0){
      foreach ($result as $item){
        array_push($series, array(
          'name' => $item['name'],
          'data' =>  array_map('intval', explode(',', substr($item['array_agg'], 1, -1))
          ))
        );
        array_push($categories,$item['d']);
      }

      $b = [];
      foreach ($categories as $key => $value) {
        array_push($b, strlen($value));
      }
      $maxKey = max(array_keys($b));


      $data = array(
        'query'  => $sql,
        'date'  => $calculateInterval,
        'series' => $series,
        'categories' => explode(',',substr(str_replace('"','',$categories[$maxKey]), 1, -1))
      );
    }*/


  }



  public function getStatusAsString(int $status)
  {
    switch ($status) {
      case Pratica::STATUS_DRAFT:
        return 'Bozza';
        break;
      case Pratica::STATUS_PAYMENT_PENDING:
        return 'Pagamento in attesa';
        break;
      case Pratica::STATUS_PRE_SUBMIT:
        return 'Inviata';
        break;
      case Pratica::STATUS_SUBMITTED:
        return 'Acquisita';
        break;
      case Pratica::STATUS_REGISTERED:
        return 'Protocollata';
        break;
      case Pratica::STATUS_PENDING:
        return 'Presa in carico';
        break;
      case Pratica::STATUS_PROCESSING:
        return 'Processata';
        break;
      case Pratica::STATUS_REQUEST_INTEGRATION:
        return 'Richiesta di integrazione';
        break;
      case Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE:
        return 'Completata in attesa di protocollazione';
        break;
      case Pratica::STATUS_COMPLETE:
        return 'Completata';
        break;
      default:
        return 'Errore';
    }
  }


}
