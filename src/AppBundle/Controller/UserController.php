<?php

namespace AppBundle\Controller;

use AppBundle\Dto\Application;
use AppBundle\Entity\CPSUser;
use AppBundle\Entity\FormIO;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\PraticaRepository;
use AppBundle\Form\IdCardType;
use AppBundle\Logging\LogConstants;
use AppBundle\Security\CPSAuthenticator;
use AppBundle\Services\CPSUserProvider;
use DateTime;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class UserController
 * @package AppBundle\Controller
 * @Route("/user")
 */
class UserController extends Controller
{
  /**
   * @Route("/", name="user_dashboard")
   * @Template()
   * @param Request $request
   * @return array
   */
  public function indexAction(Request $request)
  {
    $user = $this->getUser();

    $serviziRepository = $this->getDoctrine()->getRepository('AppBundle:Servizio');
    $servizi = $serviziRepository->findBy([], [], 3);

    $praticheRepo = $this->getDoctrine()->getRepository('AppBundle:Pratica');
    $pratiche = $praticheRepo->findBy(
      ['user' => $user],
      ['creationTime' => 'DESC'],
      3
    );

    $threads = [];

    $documents = [];
    $documentRepo = $this->getDoctrine()->getRepository('AppBundle:Document');

    $sql = 'SELECT document.id from document where document.last_read_at is null and ((readers_allowed)::jsonb @> \'"' . $user->getCodiceFiscale() . '"\' or document.owner_id = \'' . $user->getId() . '\')';
    $stmt = $this->getDoctrine()->getConnection()->prepare($sql);
    $stmt->execute();
    $documentsIds = $stmt->fetchAll();

    foreach ($documentsIds as $id) {
      $documents[] = $documentRepo->find($id);
    }

    return array(
      'user' => $user,
      'servizi' => $servizi,
      'pratiche' => $pratiche,
      'threads' => $threads,
      'documents' => $documents
    );
  }

  /**
   * @Route("/profile", name="user_profile")
   * @Template()
   * @param Request $request
   *
   * @return array|RedirectResponse
   */
  public function profileAction(Request $request)
  {
    /** @var CPSUser $user */
    $user = $this->getUser();

    $form = $this->setupSdcUserForm($user)->handleRequest($request);

    if ($form->isSubmitted()) {

      $data = $form->getData();
      $this->storeSdcUserData($user, $data, $this->get('logger'));

      $redirectRoute = $request->query->has('r') ? $request->query->get('r') : 'user_profile';
      $redirectRouteParams = $request->query->has('p') ? unserialize($request->query->get('p')) : array();
      $redirectRouteQuery = $request->query->has('p') ? unserialize($request->query->get('q')) : array();
      $this->addFlash(
        'success',$this->get('translator')->trans('aggiorna_profilo'));
      return $this->redirectToRoute($redirectRoute, array_merge($redirectRouteParams, $redirectRouteQuery));
    } else {
      if ($request->query->has('r')) {
        $this->addFlash(
          'warning',
          $this->get('translator')->trans('completa_profilo')
        );
      }
    }

    return [
      'form'      => $form->createView(),
      'user'      => $user,
    ];
  }

  private function storeSdcUserData(CPSUser $user, array $data, LoggerInterface $logger)
  {
    $manager = $this->getDoctrine()->getManager();
    $user
      ->setEmailContatto($data['email_contatto'])
      ->setCellulareContatto($data['cellulare_contatto'])
      ->setCpsTelefono($data['telefono_contatto'])
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
    $form = $formBuilder->getForm();

    return $form;
  }

  /**
   * @Route("/latest_news", name="user_latest_news")
   * @param Request $request
   *
   * @return JsonResponse
   */
  public function latestNewsAction(Request $request)
  {
    $newsProvider = $this->get('ocsdc.remote_content_provider');
    $enti = $this->getEntiFromCurrentUser();
    $data = $newsProvider->getLatestNews($enti);
    $response = new JsonResponse($data);
    $response->setMaxAge(3600);
    $response->setSharedMaxAge(3600);
    return $response;
  }

  /**
   * @Route("/latest_deadlines", name="user_latest_deadlines")
   * @param Request $request
   *
   * @return JsonResponse
   */
  public function latestDeadlinesAction(Request $request)
  {
    $newsProvider = $this->get('ocsdc.remote_content_provider');
    $enti = $this->getEntiFromCurrentUser();
    $data = $newsProvider->getLatestDeadlines($enti);
    $response = new JsonResponse($data);
    $response->setMaxAge(3600);
    $response->setSharedMaxAge(3600);
    return $response;
  }

  private function getEntiFromCurrentUser()
  {
    $entityManager = $this->getDoctrine()->getManager();
    $entiPerUser = $entityManager->createQueryBuilder()
      ->select('IDENTITY(p.ente)')->distinct()
      ->from('AppBundle:Pratica', 'p')
      ->where('p.user = :user')
      ->setParameter('user', $this->getUser())
      ->getQuery()
      ->getResult();

    $repository = $entityManager->getRepository('AppBundle:Ente');
    if (count($entiPerUser) > 0) {
      $entiPerUser = array_reduce($entiPerUser, 'array_merge', array());
      $enti = $repository->findBy(['id' => $entiPerUser]);
    } else {
      $enti = $repository->findAll();
    }

    return $enti;
  }

  /**
   * @Route("/applications", name="user_applications_json")
   * @param Request $request
   *
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

      $serializer = $this->container->get('jms_serializer');
      foreach ($data as $s) {
        $application = Application::fromEntity($s, '', false);
        $applicationArray = json_decode($serializer->serialize($application, 'json'), true);
        if ($s instanceof FormIO){
          $schema = $this->container->get('formio.factory')->createFromFormId($s->getServizio()->getFormIoId());
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
