<?php

namespace App\Controller\Ui\Frontend;

use App\Dto\ApplicationDto;
use App\Entity\CPSUser;
use App\Entity\FormIO;
use App\Entity\Pratica;
use App\Entity\PraticaRepository;
use App\Entity\ServizioRepository;
use App\Form\IdCardType;
use App\FormIO\SchemaFactoryInterface;
use App\Helpers\MunicipalityConverter;
use App\Logging\LogConstants;
use App\Services\BreadcrumbsService;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class UserController
 * @package App\Controller
 * @Route("/user")
 */
class UserController extends AbstractController
{

  /** @var LoggerInterface */
  private $logger;

  /** @var TranslatorInterface */
  private $translator;

  /** @var SerializerInterface */
  private $serializer;

  /** @var SchemaFactoryInterface */
  private $schemaFactory;
  /**
   * @var BreadcrumbsService
   */
  private $breadcrumbsService;
  /**
   * @var ApplicationDto
   */
  private $applicationDto;


  /**
   * UserController constructor.
   * @param TranslatorInterface $translator
   * @param LoggerInterface $logger
   * @param SerializerInterface $serializer
   * @param SchemaFactoryInterface $schemaFactory
   * @param BreadcrumbsService $breadcrumbsService
   * @param ApplicationDto $applicationDto
   */
  public function __construct(
    TranslatorInterface $translator,
    LoggerInterface $logger,
    SerializerInterface $serializer,
    SchemaFactoryInterface $schemaFactory,
    BreadcrumbsService $breadcrumbsService,
    ApplicationDto $applicationDto
  )
  {
    $this->logger = $logger;
    $this->translator = $translator;
    $this->serializer = $serializer;
    $this->schemaFactory = $schemaFactory;
    $this->breadcrumbsService = $breadcrumbsService;
    $this->applicationDto = $applicationDto;
  }


  /**
   * @Route("/", name="user_dashboard")
   * @param Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function indexAction(Request $request)
  {

    $this->breadcrumbsService->getBreadcrumbs()->addRouteItem($this->translator->trans('nav.dashboard'), 'user_dashboard');
    $user = $this->getUser();

    /** @var ServizioRepository $serviziRepository */
    $serviziRepository = $this->getDoctrine()->getRepository('App\Entity\Servizio');
    $servizi = $serviziRepository->findStickyAvailable('name', true, 3);

    $praticheRepo = $this->getDoctrine()->getRepository('App\Entity\Pratica');
    $pratiche = $praticheRepo->findBy(
      ['user' => $user],
      ['creationTime' => 'DESC'],
      3
    );

    $documents = [];
    // Todo: spostare in DocumentsRepository
    $documentRepo = $this->getDoctrine()->getRepository('App\Entity\Document');
    $sql = 'SELECT document.id from document where document.last_read_at is null and ((readers_allowed)::jsonb @> \'"' . $user->getCodiceFiscale() . '"\' or document.owner_id = \'' . $user->getId() . '\')';
    $stmt = $this->getDoctrine()->getConnection()->prepare($sql);
    $documentsIds = $stmt->executeQuery()->fetchAllAssociative();

    foreach ($documentsIds as $id) {
      $documents[] = $documentRepo->find($id);
    }

    return $this->render( 'User/index.html.twig', [
      'user' => $user,
      'servizi' => $servizi,
      'pratiche' => $pratiche,
      'documents' => $documents
    ]);
  }

  /**
   * @Route("/profile", name="user_profile")
   * @param Request $request
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function profileAction(Request $request)
  {

    $this->breadcrumbsService->getBreadcrumbs()->addRouteItem($this->translator->trans('nav.profilo'), 'user_profile');

    /** @var CPSUser $user */
    $user = $this->getUser();

    $form = $this->setupSdcUserForm($user)->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $data = $form->getData();
      $this->storeSdcUserData($user, $data, $this->logger);

      $redirectRoute = $request->query->has('r') ? $request->query->get('r') : 'user_profile';
      $redirectRouteParams = $request->query->has('p') ? unserialize($request->query->get('p')) : [];
      $redirectRouteQuery = $request->query->has('q') ? unserialize($request->query->get('q')) : [];
      $params = array_merge($redirectRouteParams, $redirectRouteQuery) ?? [];
      $this->addFlash('success',$this->translator->trans('aggiorna_profilo'));

      try {
        $redirectUrl = $this->generateUrl($redirectRoute, $params);
        return $this->redirect($redirectUrl);
      } catch (\Exception $e) {
        $this->logger->error('Error generating redirect url after profile update: ', ['redirect_route' => $redirectRoute, 'params' => $params]);
      }
      return $this->redirectToRoute('user_profile');
    } else {
      if ($request->query->has('r')) {
        $this->addFlash(
          'warning',
          $this->translator->trans('completa_profilo')
        );
      }
    }

    return $this->render( 'User/profile.html.twig', [
      'form'      => $form->createView(),
      'user'      => $user,
    ]);
  }

  private function storeSdcUserData(CPSUser $user, array $data, LoggerInterface $logger)
  {
    $manager = $this->getDoctrine()->getManager();
    $user
      ->setEmailContatto($data['email_contatto'])
      ->setCellulareContatto($data['cellulare_contatto'])
      ->setCpsTelefono($data['telefono_contatto'])
      ->setDataNascita($data['data_nascita'])
      ->setLuogoNascita($data['luogo_nascita'])
      ->setStatoNascita($data['stato_nascita'])
      ->setIdCard($data['id_card'])
      ->setSdcIndirizzoResidenza($data['sdc_indirizzo_residenza'])
      ->setSdcCapResidenza($data['sdc_cap_residenza'])
      ->setSdcCittaResidenza($data['sdc_citta_residenza'])
      ->setSdcProvinciaResidenza($data['sdc_provincia_residenza'])
      ->setSdcStatoResidenza($data['sdc_stato_residenza'])
      ->setSdcIndirizzoDomicilio($data['sdc_indirizzo_domicilio'])
      ->setSdcCapDomicilio($data['sdc_cap_domicilio'])
      ->setSdcCittaDomicilio($data['sdc_citta_domicilio'])
      ->setSdcProvinciaDomicilio($data['sdc_provincia_domicilio'])
      ->setSdcStatoDomicilio($data['sdc_stato_domicilio']);

    if (!$user->getSesso() && isset($data['sesso'])){
      $user->setSesso($data['sesso']);
    }

    if (!$user->getProvinciaNascita() && isset($data['provincia_nascita'])){
      $user->setProvinciaNascita($data['provincia_nascita']);
    }

    $manager->persist($user);

    try {
      $manager->flush();
      $logger->info(LogConstants::USER_HAS_CHANGED_CONTACTS_INFO, ['userid' => $user->getId()]);
    } catch (\Exception $e) {
      $logger->error($e->getMessage());
    }
  }

  private function setupSdcUserForm(CPSUser $user)
  {
    $compiledCellulareData = $user->getCellulare();
    $compiledEmailData = $user->getEmail();
    $compiledPhoneData = $user->getTelefono();

    $compiledProvinciaNascita =  $user->getProvinciaNascita();

    $compiledIndirizzoResidenza = $user->getIndirizzoResidenza();
    $compiledCapResidenza = $user->getCapResidenza();
    $compiledCittaResidenza = $user->getMunicipalityFromCode($user->getCittaResidenza());
    $compiledProvinciaResidenza = $user->getProvinciaResidenza();
    $compiledStatoResidenza = $user->getStatoResidenza();

    $compiledIndirizzoDomicilio = $user->getIndirizzoDomicilio();
    $compiledCapDomicilio = $user->getCapDomicilio();
    $compiledCittaDomicilio = $user->getMunicipalityFromCode($user->getCittaDomicilio());
    $compiledProvinciaDomicilio = $user->getProvinciaDomicilio();
    $compiledStatoDomicilio = $user->getStatoDomicilio();

    //Se la email contiene il valore fake, forziamo l'utente a riscrivere una mail corretta resettando il campo
    $regex = "/[^@]*(".$user::FAKE_EMAIL_DOMAIN.")/";
    if (preg_match($regex, $compiledEmailData)) {
      $this->addFlash(
        'danger',$this->translator->trans('fake_email_message'));
      $compiledEmailData = '';
    }

    $formBuilder = $this->createFormBuilder(null, ['attr' => ['id' => 'edit_user_profile']])
      ->add('email_contatto', EmailType::class,
        ['label' => false, 'data' => $compiledEmailData, 'required' => false]
      )
      ->add('cellulare_contatto', TextType::class,
        ['label' => false, 'data' => $compiledCellulareData, 'required' => false]
      )
      ->add('telefono_contatto', TextType::class,
        ['label' => false, 'data' => $compiledPhoneData, 'required' => false]
      )
      ->add('id_card', IdCardType::class,
        ['label' => false, 'data' => $user->getIdCard(), 'required' => false]
      )
      ->add('data_nascita', DateType::class,
        ['widget' => 'single_text', 'required' => true, 'label' => false, 'data' => $user->getDataNascita(),'label_attr' => ['class' => 'active']]
      )
      ->add('stato_nascita', TextType::class,
        ['label' => false, 'data' => $user->getStatoNascita(), 'required' => false]
      )
      ->add('sdc_indirizzo_residenza', TextType::class,
        ['label' => false, 'data' => $compiledIndirizzoResidenza, 'required' => false]
      )
      ->add('sdc_cap_residenza', TextType::class,
        ['label' => false, 'data' => $compiledCapResidenza, 'required' => false]
      )
      ->add('sdc_citta_residenza', TextType::class,
        ['label' => false, 'data' => $compiledCittaResidenza, 'required' => false]
      )
      ->add('sdc_provincia_residenza', ChoiceType::class, [
        'label' => false,
        'data' => $compiledProvinciaResidenza,
        'choices' => CPSUser::getProvinces(),
        'required' => false
      ])
      ->add('sdc_stato_residenza', TextType::class,
        ['label' => false, 'data' => $compiledStatoResidenza, 'required' => false]
      )
      ->add('sdc_indirizzo_domicilio', TextType::class,
        ['label' => false, 'data' => $compiledIndirizzoDomicilio, 'required' => false]
      )
      ->add('sdc_cap_domicilio', TextType::class,
        ['label' => false, 'data' => $compiledCapDomicilio, 'required' => false]
      )
      ->add('sdc_citta_domicilio', TextType::class,
        ['label' => false, 'data' => $compiledCittaDomicilio, 'required' => false]
      )
      ->add('sdc_provincia_domicilio', ChoiceType::class, [
        'label' => false,
        'data' => $compiledProvinciaDomicilio,
        'choices' => CPSUser::getProvinces(),
        'required' => false
      ])
      ->add('sdc_stato_domicilio', TextType::class,
        ['label' => false, 'data' => $compiledStatoDomicilio, 'required' => false]
      )
      ->add('save', SubmitType::class,
        ['label' => 'user.profile.salva']
      );

    if (!$user->getSesso()){
      $formBuilder->add('sesso', ChoiceType::class,
        ['label' => false, 'required' => true, 'choices' => ['user.profile.genere.maschio' => 'M', 'user.profile.genere.femmina' => 'F']]
      );
    }

    // Luogo di nascita
    try {
      MunicipalityConverter::translate($user->getLuogoNascita());
      $formBuilder->add('luogo_nascita', ChoiceType::class,
        ['label' => false, 'required' => true, 'data' => $user->getMunicipalityFromCode($user->getLuogoNascita()), 'choices' => MunicipalityConverter::getCodes(),  'choice_label' => function ($value) {
          return $value;
        }]
      );
    } catch (\Exception $e) {
      $formBuilder->add('luogo_nascita', TextType::class,
        ['label' => false, 'data' => $user->getLuogoNascita(), 'required' => true]
      );
    }

    if (!$user->getProvinciaNascita()){
      $formBuilder->add('provincia_nascita', ChoiceType::class,
        ['label' => false, 'required' => true, 'choices' => CPSUser::getProvinces()]
      );
    }
    $formBuilder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmitUserForm'));

    $form = $formBuilder->getForm();

    return $form;
  }

  public function onPreSubmitUserForm(FormEvent $event){
    $data = $event->getData();

    if (!isset($data['luogo_nascita']) or !$data['luogo_nascita'])
    {
      $event->getForm()->addError(
        new FormError($this->translator->trans('user.error_birth_place'))
      );
    }
  }

  /**
   * @Route("/applications", name="user_applications_json")
   * @param Request $request
   * @deprecated deprecated since version 1.5.3
   * @return JsonResponse
   */
  public function applicationsAction(Request $request)
  {
    $result = [];

    $parameters = [
      'data' => $request->get('data', []),
      'service' => $request->get('service', []),
      'status' => $request->get('status', []),
      'sort' => $request->get('sort', 'submissionTime'),
      'order' => $request->get('order', 'asc'),
    ];

    foreach ($request->query->keys() as $queryParameter){
      if (!array_key_exists($queryParameter, $parameters) && !in_array($queryParameter, ['limit', 'offset', 'output']) ){
        $error = [
          'error' => 'Bad Request',
          'message' => "Unexpected parameter: $queryParameter"
        ];
        $request->setRequestFormat('json');
        return new JsonResponse(json_encode($error), 400, [], true);
      }
    }

    /** @var PraticaRepository $praticaRepository */
    $praticaRepository = $this->getDoctrine()->getRepository(Pratica::class);

    /** @var CPSUser $user */
    $user = $this->getUser();

    $count = $praticaRepository->countPraticheByUser($user, $parameters);
    $output = $request->get('output', 'count');

    $result['meta']['count'] = $count;
    $currentParameters = $parameters;

    if ($output !== 'count'){
      $limit = intval($request->get('limit', 10));
      $offset = intval($request->get('offset', 0));
      $data = $praticaRepository->findPraticheByUser($user, $parameters, $limit, $offset);

      $result['data'] = [];
      $currentParameters['offset'] = $offset;
      $currentParameters['limit'] = $limit;
      $result['links']['self'] = $this->generateUrl('user_applications_json', $currentParameters);
      $result['links']['prev'] = null;
      $result['links']['next'] = null;
      if ($offset != 0) {
        $prevParameters = $parameters;
        $prevParameters['offset'] = $offset - $limit;
        $prevParameters['limit'] = $limit;
        $result['links']['prev'] = $this->generateUrl('user_applications_json', $prevParameters);
      }
      if ($offset + $limit < $count) {
        $nextParameters = $parameters;
        $nextParameters['offset'] = $offset + $limit;
        $nextParameters['limit'] = $limit;
        $result['links']['next'] = $this->generateUrl('user_applications_json', $nextParameters);
      }

      foreach ($data as $s) {
        $application = $this->applicationDto->fromEntity($s, false);
        $applicationArray = json_decode($this->serializer->serialize($application, 'json'), true);
        if ($s instanceof FormIO){
          $schema = $this->schemaFactory->createFromFormId($s->getServizio()->getFormIoId());
          if ($schema->hasComponents()) {
            $dematerialized = $s->getDematerializedForms();
            if (isset($dematerialized['data'])) {
              $applicationArray['data'] = $schema->getDataBuilder()->setDataFromArray($dematerialized['data'])->toFullFilledFlatArray();
            } else {
              $applicationArray['data'] = array_fill_keys($schema->getComponentsColumns('name'), '');
            }
          }
        }
        $result['data'][] = $applicationArray;
      }
    }

    $result['meta']['parameter'] = $currentParameters;

    $request->setRequestFormat('json');
    return new JsonResponse(json_encode($result), 200, [], true);
  }
}
