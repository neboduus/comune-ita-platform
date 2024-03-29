<?php

namespace App\Controller\Ui\Backend;

use App\Dto\ApplicationDto;
use App\Dto\ApplicationOutcome;
use App\Entity\Allegato;
use App\Entity\AllegatoOperatore;
use App\Entity\CPSUser;
use App\Entity\DematerializedFormPratica;
use App\Entity\FormIO;
use App\Entity\Message;
use App\Entity\OperatoreUser;
use App\Entity\Pratica;
use App\Entity\PraticaRepository;
use App\Entity\Servizio;
use App\Entity\StatusChange;
use App\Entity\UserGroup;
use App\Form\Extension\TestiAccompagnatoriProcedura;
use App\Form\Operatore\Base\ApplicationOutcomeType;
use App\FormIO\Schema;
use App\FormIO\SchemaFactoryInterface;
use App\Logging\LogConstants;
use App\Security\Voters\ApplicationVoter;
use App\Services\FormServerApiAdapterService;
use App\Services\InstanceService;
use App\Services\Manager\MessageManager;
use App\Services\Manager\PraticaManager;
use App\Services\PraticaStatusService;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\FetchMode;
use Doctrine\ORM\EntityManagerInterface;
use Flagception\Manager\FeatureManagerInterface;
use JMS\Serializer\SerializerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class OperatoriController
 * @Route("/operatori")
 */
class OperatoriController extends AbstractController
{

  /** @var SchemaFactoryInterface */
  private $schemaFactory;

  /** @var SerializerInterface */
  private $serializer;

  /** @var TranslatorInterface */
  private $translator;

  /** * @var LoggerInterface */
  private $logger;

  /** * @var PraticaStatusService */
  private $praticaStatusService;

  /** @var InstanceService */
  private $instanceService;

  /* @var EntityManagerInterface */
  private $entityManager;
  /**
   * @var FeatureManagerInterface
   */
  private $featureManager;

  /**
   * @var PraticaManager
   */
  private $praticaManager;

  /** @var MessageManager */
  private $messageManager;

  /** @var JWTTokenManagerInterface */
  private $JWTTokenManager;
  /**
   * @var FormServerApiAdapterService
   */
  private $formServerService;
  /**
   * @var ApplicationDto
   */
  private $applicationDto;

  /**
   * OperatoriController constructor.
   * @param SchemaFactoryInterface $schemaFactory
   * @param SerializerInterface $serializer
   * @param TranslatorInterface $translator
   * @param LoggerInterface $logger
   * @param PraticaStatusService $praticaStatusService
   * @param InstanceService $instanceService
   * @param EntityManagerInterface $entityManager
   * @param FeatureManagerInterface $featureManager
   * @param PraticaManager $praticaManager
   * @param MessageManager $messageManager
   * @param JWTTokenManagerInterface $JWTTokenManager
   * @param FormServerApiAdapterService $formServerService
   * @param ApplicationDto $applicationDto
   */
  public function __construct(
    SchemaFactoryInterface      $schemaFactory,
    SerializerInterface         $serializer,
    TranslatorInterface         $translator,
    LoggerInterface             $logger,
    PraticaStatusService        $praticaStatusService,
    InstanceService             $instanceService,
    EntityManagerInterface      $entityManager,
    FeatureManagerInterface     $featureManager,
    PraticaManager              $praticaManager,
    MessageManager              $messageManager,
    JWTTokenManagerInterface    $JWTTokenManager,
    FormServerApiAdapterService $formServerService,
    ApplicationDto              $applicationDto
  )
  {
    $this->schemaFactory = $schemaFactory;
    $this->serializer = $serializer;
    $this->translator = $translator;
    $this->logger = $logger;
    $this->praticaStatusService = $praticaStatusService;
    $this->instanceService = $instanceService;
    $this->entityManager = $entityManager;
    $this->featureManager = $featureManager;
    $this->praticaManager = $praticaManager;
    $this->messageManager = $messageManager;
    $this->JWTTokenManager = $JWTTokenManager;
    $this->formServerService = $formServerService;
    $this->applicationDto = $applicationDto;
  }


  /**
   * @Route("/",name="operatori_index")
   * @return Response
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
      ],
      [
        'name' => 'asc'
      ]
    );

    $result = [];
    /** @var Servizio $s */
    foreach ($servizi as $s) {
      if ($s->getServiceGroup()) {
        $result[$s->getServiceGroup()->getSlug()]['group'] = $s->getServiceGroup();
        $result[$s->getServiceGroup()->getSlug()]['services'][$s->getSlug()] = $s;
      } else {
        $result[$s->getSlug()] = $s;
      }
    }


    $stati = [];
    foreach ($praticaRepository->getStateListByOperatore($user, PraticaRepository::OPERATORI_LOWER_STATE) as $state) {
      $state['name'] = $this->translator->trans($state['name']);
      $stati[] = $state;
    }

    return $this->render('Operatori/index.html.twig', [
      'servizi' => $result,
      'stati' => $stati,
      'user' => $this->getUser(),
    ]);
  }

  /**
   * @Route("/pratiche",name="operatori_index_json")
   * @param Request $request
   * @return JsonResponse
   */
  public function indexJsonAction(Request $request)
  {
    $limit = intval($request->get('limit', 10));
    $offset = intval($request->get('offset', 0));
    $result = $this->getFilteredPraticheByOperatore($request, $limit, $offset);

    $request->setRequestFormat('json');
    return new JsonResponse(json_encode($result), 200, [], true);
  }

  /**
   * @Route("/pratiche/{servizio}/new", name="new_application_by_operator")
   * @param Request $request
   * @param Servizio $servizio
   * @return Response
   */
  public function newAppicationByOperatorAction(Request $request, Servizio $servizio)
  {
    // Todo: rimuoveve non appena sarà possibile compilare una pratica built-in per conto del cittadino
    if ($servizio->isBuiltIn()) {
      $this->addFlash('error', $this->translator->trans('operatori.error_compile_built-in'));
      return $this->redirectToRoute('backend_services_index');
    }
    $userId = $request->query->get('user', false);

    $application = new Pratica();
    $application->setServizio($servizio);

    $cpsUserData = false;
    if ($userId) {
      $cpsUserRepo = $this->entityManager->getRepository('App\Entity\CPSUser');
      $cpsUser = $cpsUserRepo->find($userId);
      if ($cpsUser instanceof CPSUser) {
        $application->setUser($cpsUser);
        $schema = $this->schemaFactory->createFromFormId($servizio->getFormIoId());
        $cpsUserData = ['data' => $this->praticaManager->getMappedFormDataWithUserData($schema, $cpsUser)];
      }
    }

    return $this->render('Operatori/newApplication.html.twig', [
      'formserver_url' => $this->getParameter('formserver_admin_url'),
      'user' => $this->getUser(),
      'token' => $this->JWTTokenManager->create($this->getUser()),
      'service' => $servizio,
      'application' => $application,
      'cps_user_data' => $cpsUserData
    ]);
  }

  /**
   * @Route("/pratiche/csv",name="operatori_index_csv")
   * @param Request $request
   */
  public function indexCSVAction(Request $request)
  {
    $responseCallback = function () use ($request) {

      /** @var OperatoreUser $user */
      $user = $this->getUser();

      /** @var PraticaRepository $praticaRepository */
      $praticaRepository = $this->getDoctrine()->getRepository(Pratica::class);
      $servizi = $this->getDoctrine()->getRepository(Servizio::class)->findBy([
        'id' => $praticaRepository->getServizioIdListByOperatore($user, PraticaRepository::OPERATORI_LOWER_STATE),
      ]);
      $extraHeaders = $request->get('extra_headers', []);
      $handle = fopen('php://output', 'w');
      $result = $this->getFilteredPraticheByOperatore($request, 1, 0);
      $schema = (array)$result['meta']['schema'];

      $csvHeaders = [
        'ID',
        'Numero di protocollo',
        'Login',
        'Pagamenti',
        'Richiedente',
        'Codice fiscale',
        'Data di inserimento',
        'Ultimo cambio stato',
        'Stato',
        'Operatore',
        'Servizio',
      ];
      $extraValues = [];
      foreach ($schema as $item) {
        if (in_array(trim($item['label']), $extraHeaders)) {
          $extraValues[$item['name']] = trim($item['label']);
        }
      }
      $csvHeaders = array_merge($csvHeaders, array_values($extraValues));
      fputcsv($handle, $csvHeaders);

      $dataCount = 0;
      $totalCount = $result['meta']['count'];
      $limit = 100;
      $offset = 0;

      while ($dataCount < $totalCount) {
        $result = $this->getFilteredPraticheByOperatore($request, $limit, $offset);
        $data = $result['data'];
        $dataCount += count($data);
        $offset += $limit;
        foreach ($data as $item) {
          $serviceName = '?';
          foreach ($servizi as $servizio) {
            if ($item['service'] == $servizio->getSlug()) {
              $serviceName = $servizio->getName();
            }
          }
          $csvRow = [
            $item['id'],
            isset($item['protocol_number']) ? $item['protocol_number'] : '',
            $item['idp'],
            $item['is_payment_required'] ? $item['payment_complete'] : '',
            $item['user_name'],
            $item['codice_fiscale'],
            isset($item['submission_time']) ? date('d/m/Y H:i:s', $item['submission_time']) : '',
            isset($item['latest_status_change_time']) ? date('d/m/Y H:i:s', $item['latest_status_change_time']) : '',
            $this->translator->trans('pratica.dettaglio.stato_' . $item['status']),
            $item['operator_name'],
            $serviceName,
          ];

          foreach ($item['data'] as $key => $value) {
            if (isset($extraValues[$key])) {
              $csvRow[$key] = is_array($value) ? count($value) : $value;
            }
          }
          fputcsv($handle, array_values($csvRow));
          flush();
        }
        $this->getDoctrine()->getManager()->clear();
      }

      fclose($handle);
    };

    $fileNameCreationDate = new \DateTime();
    $fileName = 'export_' . $fileNameCreationDate->format('d-m-yy-H-m') . '.csv';
    $response = new StreamedResponse();
    $response->headers->set('Content-Encoding', 'none');
    $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
    $response->headers->set('X-Accel-Buffering', 'no');
    $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      $fileName
    ));
    $response->headers->set('Content-Description', 'File Transfer');
    $response->setStatusCode(Response::HTTP_OK);
    $response->setCallback($responseCallback);
    $response->send();
  }

  /**
   * @Route("/pratiche/calculate",name="operatori_index_calculate")
   * @param Request $request
   * @return JsonResponse
   */
  public function indexCalculateAction(Request $request)
  {
    $result = [];
    $functions = [
      'sum' => function (PraticaRepository $praticaRepository, array $fields, OperatoreUser $user, array $parameters) {
        return $praticaRepository->getSumFieldsInPraticheByOperatore(
          $fields,
          $user,
          $parameters
        );
      },
      'avg' => function (PraticaRepository $praticaRepository, array $fields, OperatoreUser $user, array $parameters) {
        return $praticaRepository->getAvgFieldsInPraticheByOperatore(
          $fields,
          $user,
          $parameters
        );
      },
      'count' => function (PraticaRepository $praticaRepository, array $fields, OperatoreUser $user, array $parameters) {
        return $praticaRepository->getCountNotNullFieldsInPraticheByOperatore(
          $fields,
          $user,
          $parameters
        );
      },
    ];
    /** @var PraticaRepository $praticaRepository */
    $praticaRepository = $this->getDoctrine()->getRepository(Pratica::class);
    /** @var OperatoreUser $user */
    $user = $this->getUser();
    $parameters = $this->getPraticheFilters($request);
    $servizioId = $parameters['servizio'];
    if ($servizioId) {
      $servizio = $this->getDoctrine()->getManager()->getRepository(Servizio::class)->findOneBy(['id' => $servizioId]);
      if ($servizio instanceof Servizio) {
        /** @var Schema $schema */
        $schema = $this->schemaFactory->createFromFormId($servizio->getFormIoId());
        foreach ($functions as $name => $callable) {
          $requestFields = $request->get($name, []);
          if (!empty($requestFields)) {
            $fields = [];
            foreach ($requestFields as $requestField) {
              if ($schema->hasComponent($requestField)) {
                $fields[] = $schema->getComponent($requestField);
              }
            }
            if (!empty($fields)) {
              $result[$name] = call_user_func($callable, $praticaRepository, $fields, $user, $parameters);
            }
          }
        }
      }
    }
    $request->setRequestFormat('json');
    return new JsonResponse(json_encode($result), 200, [], true);
  }

  private function getPraticheFilters($request)
  {
    return [
      'gruppo' => $request->get('gruppo', false),
      'servizio' => $request->get('servizio', false),
      'stato' => $request->get('stato', false),
      'workflow' => $request->get('workflow', false),
      'query_field' => $request->get('query_field', false),
      'query' => $request->get('query', false),
      'sort' => $request->get('sort', 'submissionTime'),
      'order' => $request->get('order', 'asc'),
      'collate' => (int)$request->get('collate', false),
      'last_status_change' => (array)$request->get('last_status_change', []),
    ];
  }

  /**
   * @param Request $request
   * @param $limit
   * @param $offset
   * @return array
   * @todo mergiare questa logica in ApplicationsAPIController o in PraticaRepository?
   */
  private function getFilteredPraticheByOperatore($request, $limit, $offset)
  {
    $parameters = $this->getPraticheFilters($request);
    /** @var PraticaRepository $praticaRepository */
    $praticaRepository = $this->entityManager->getRepository(Pratica::class);
    /** @var OperatoreUser $user */
    $user = $this->getUser();

    $filters = [];

    try {
      $count = $praticaRepository->countPraticheByOperatore($user, $parameters);
      /** @var Pratica[] $data */
      $data = $praticaRepository->findPraticheByOperatore($user, $parameters, $limit, $offset);
      $tempParameters = $parameters;
      unset($tempParameters['stato']);
      $tempStates = $praticaRepository->findStatesPraticheByOperatore($user, $tempParameters);
      foreach ($tempStates as $state) {
        $state['name'] = $this->translator->trans($state['name']);
        $filters['states'][] = $state;
      }
    } catch (\Throwable $e) {
      $this->logger->error('An error occurred while retrieving filtered applications by operator: ' . $e->getMessage());
      $count = 0;
      $data = [];
      $result['meta']['error'] = true; //$e->getMessage();
    }

    $schema = null;
    $result = [];
    $result['meta']['schema'] = false;
    $servizioId = $parameters['servizio'];
    if ($servizioId && $count > 0) {
      $servizio = $this->entityManager->getRepository(Servizio::class)->findOneBy(['id' => $servizioId]);
      if ($servizio instanceof Servizio) {
        $schema = $this->schemaFactory->createFromFormId($servizio->getFormIoId());
        if ($schema->hasComponents()) {
          $result['meta']['schema'] = $schema->getComponents();
        }
      }
    }

    $result['filters'] = $filters;
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

    foreach ($data as $s) {
      //load Application Dto without file collection to reduce the number of db queries
      $application = $this->applicationDto->fromEntity($s, false);
      $applicationArray = json_decode($this->serializer->serialize($application, 'json'), true);
      $minimunStatusForAssign = $s->getServizio()->isProtocolRequired() ? Pratica::STATUS_REGISTERED : Pratica::STATUS_SUBMITTED;
      $applicationArray['can_autoassign'] = $s->getOperatore() == null && $s->getStatus() >= $minimunStatusForAssign;
      $applicationArray['is_protocollo_required'] = $s->getServizio()->isProtocolRequired();
      $applicationArray['is_payment_required'] = !empty($s->getPaymentData());
      $applicationArray['payment_complete'] = $s->getStatus() == Pratica::STATUS_PAYMENT_ERROR || $s->getStatus() <= Pratica::STATUS_PAYMENT_OUTCOME_PENDING ? false : true;
      $applicationArray['idp'] = $s->getAuthenticationData()->getAuthenticationMethod() ? $s->getAuthenticationData()->getAuthenticationMethod() : $s->getUser()->getIdp();
      $applicantUser = $s->getUser();
      $codiceFiscale = $applicantUser instanceof CPSUser ? $applicantUser->getCodiceFiscale() : '';
      $codiceFiscaleParts = explode('-', $codiceFiscale);
      $applicationArray['codice_fiscale'] = array_shift($codiceFiscaleParts);
      $applicationArray['operator_name'] = $s->getOperatore() ? $s->getOperatore()->getFullName() : null;
      //@todo check perfomance: children count add one additional db query each result
      $applicationArray['children_count'] = $parameters['collate'] ? $s->getChildren()->count() : null;
      $applicationArray['group'] = $parameters['collate'] && $s->getFolderId() != null ? true : false;

      try {
        $this->denyAccessUnlessGranted(ApplicationVoter::VIEW, $s);
        $applicationArray['can_read'] = true;
      } catch (UnauthorizedHttpException $e) {
        $applicationArray['can_read'] = false;
      }

      if (isset($schema) && $schema->hasComponents() && $s instanceof FormIO) {
        $dematerialized = $s->getDematerializedForms();
        if (isset($dematerialized['data'])) {
          $applicationArray['data'] = $schema->getDataBuilder()->setDataFromArray($dematerialized['data'])->toFullFilledFlatArray();
        } else {
          $applicationArray['data'] = array_fill_keys($schema->getComponentsColumns('name'), '');
        }
      }

      $result['data'][] = $applicationArray;
    }

    return $result;
  }

  /**
   * @Route("/usage",name="operatori_usage")
   * @return Response
   */
  public function usageAction()
  {
    $serviziRepository = $this->getDoctrine()->getRepository('App\Entity\Servizio');
    $servizi = $serviziRepository->findBy(
      [
        'status' => Servizio::STATUS_AVAILABLE
      ]
    );

    $timeZone = date_default_timezone_get();
    $sql = "SELECT COUNT(p.id), date_trunc('year', TO_TIMESTAMP(p.submission_time) AT TIME ZONE '" . $timeZone . "') AS tslot
            FROM pratica AS p WHERE p.status > 1000 and p.submission_time IS NOT NULL GROUP BY tslot ORDER BY tslot ASC";

    try {
      $stmt = $this->entityManager->getConnection()->prepare($sql);
      $stmt->execute();
      $result = $stmt->fetchAll(FetchMode::ASSOCIATIVE);
    } catch (DBALException $e) {
      $this->logger->error($e->getMessage());
      $result = [];
    }

    $statusServices = $this->populateSelectStatusServicesPratiche();
    return $this->render('Operatori/usage.html.twig', [
      'servizi' => $servizi,
      'pratiche' => $result,
      'user' => $this->getUser(),
      'statusServices' => $statusServices
    ]);
  }

  /**
   * @Route("/{pratica}/autoassign",name="operatori_autoassing_pratica")
   * @param Pratica $pratica
   *
   * @return RedirectResponse
   * @throws \Exception
   */
  public function autoAssignPraticaAction(Pratica $pratica)
  {
    /** @var OperatoreUser $user */
    $user = $this->getUser();

    try {
      $this->praticaManager->assign($pratica, $user);
    } catch (\Exception $e) {
      $this->addFlash('error', $e->getMessage());
    }

    return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica]);
  }

  /**
   * @Route("/{pratica}/reassign",name="operatori_reassign_pratica")
   * @param Pratica $pratica
   *
   * @return RedirectResponse
   * @throws \Exception
   */
  public function reassignPraticaAction(Pratica $pratica): RedirectResponse
  {
    /** @var OperatoreUser $user */
    $user = $this->getUser();
    $this->denyAccessUnlessGranted(ApplicationVoter::VIEW, $pratica);

    try {
      if ($pratica->getOperatore() === null) {
        throw new BadRequestHttpException(
          "La pratica non è assegnata ad alcun operatore"
        );
      }

      $this->reassignPratica($pratica, $user);
    } catch (\Exception $e) {
      $this->addFlash('error', $e->getMessage());
    }

    return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica]);
  }

  /**
   * @Route("/{pratica}/change-assignee",name="operatori_pratica_change_assignee")
   * @param Pratica|DematerializedFormPratica $pratica
   * @return void
   */
  public function changeAssegneePraticaAction(Pratica $pratica, Request $request): RedirectResponse
  {
    $this->denyAccessUnlessGranted(ApplicationVoter::VIEW, $pratica);

    try {
      $userGroupId = $request->request->get('user_group', false);
      $operatorId = $request->request->get('operator', false);

      if (!$userGroupId) {
        throw new BadRequestHttpException($this->translator->trans('operatori.user_group_is_required'));
      }
      $userGroup = $this->entityManager->getRepository(UserGroup::class)->find($userGroupId);
      if (!$userGroup) {
        throw new BadRequestHttpException($this->translator->trans('operatori.user_group_not_found'));
      }

      $operator = null;
      if ($operatorId) {
        $operator = $this->entityManager->getRepository(OperatoreUser::class)->find($operatorId);
        if (!$operator) {
          throw new BadRequestHttpException($this->translator->trans('operatori.operator_not_found'));
        }
      }

      $this->reassignPratica($pratica, $operator, $userGroup);

    } catch (\Exception $e) {
      $this->addFlash('error', $e->getMessage());
    }

    // User can no longer access application -> redirect to applications index
    if (!$this->isGranted(ApplicationVoter::VIEW, $pratica)) {
      return $this->redirectToRoute('operatori_index');
    }
    return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica]);
  }

  /**
   * @throws \Exception
   */
  private function reassignPratica(Pratica $pratica, OperatoreUser $operatoreUser=null, UserGroup $userGroup=null)
  {
    $oldUser = $pratica->getOperatore() ?? $pratica->getUserGroup();
    $this->praticaManager->assign($pratica, $operatoreUser, $userGroup);
    $this->entityManager->flush($pratica);

    if (!$userGroup) {
      $this->addFlash('warning', $this->translator->trans('operatori.check_user_group'));
    }

    $this->logger->info(
      LogConstants::PRATICA_REASSIGNED,
      [
        'pratica' => $pratica->getId(),
        'user' => $pratica->getOperatore() ? $pratica->getOperatore()->getId() : $pratica->getUserGroup()->getId(),
        'old_user' => $oldUser->getId(),
      ]
    );
  }

  /**
   * @Route("/{pratica}/detail",name="operatori_show_pratica")
   * @param Pratica|DematerializedFormPratica $pratica
   * @param Request $request
   * @return Response
   */
  public function showPraticaAction(Pratica $pratica, Request $request)
  {

    /** @var OperatoreUser $user */
    $user = $this->getUser();
    $this->denyAccessUnlessGranted(ApplicationVoter::VIEW, $pratica);

    $tab = $request->query->get('tab');

    $attachments = $this->entityManager->getRepository('App\Entity\Pratica')->getMessageAttachments(['author' => $pratica->getUser()->getId()], $pratica);

    /** @var CPSUser $applicant */
    $applicant = $pratica->getUser();

    $messageForm = $this->setupMessageForm($pratica);
    $messageForm->handleRequest($request);
    if ($messageForm->isSubmitted()) {

      $visibility = $messageForm->getClickedButton()->getName();
      // Funzionalità non disponibile agli utenti anonimi
      $authData = $pratica->getAuthenticationData();
      if (isset($authData['authenticationMethod']) && $authData['authenticationMethod'] == CPSUser::IDP_NONE && $visibility == Message::VISIBILITY_APPLICANT) {
        $messageForm->addError(new FormError($this->translator->trans('operatori.messaggi.non_disponibile_anonimo')));
      }

      // E' necessario prendere in carico la pratica per inviare messaggi pubblici
      if (!$pratica->getOperatore() && $visibility == Message::VISIBILITY_APPLICANT) {
        $messageForm->addError(new FormError($this->translator->trans('operatori.messaggi.prendi_in_carico_per_abilitare')));
      }

      if ($messageForm->isValid()) {
        /** @var Message $message */
        $message = $messageForm->getData();

        $callToActions = [
          ['label' => 'view', 'link' => $this->generateUrl('pratica_show_detail', ['pratica' => $pratica, 'tab' => 'note'], UrlGeneratorInterface::ABSOLUTE_URL)],
          ['label' => 'reply', 'link' => $this->generateUrl('pratica_show_detail', ['pratica' => $pratica, 'tab' => 'note'], UrlGeneratorInterface::ABSOLUTE_URL)],
        ];

        $message->setProtocolRequired(false);
        $message->setVisibility($visibility);
        $message->setCallToAction($callToActions);

        $this->messageManager->save($message);

        return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica, 'tab' => 'note']);
      }
    }

    $outcome = (new ApplicationOutcome())->setApplicationId($pratica->getId());
    $options["helper"] = new TestiAccompagnatoriProcedura($this->translator, $this->getParameter('prefix') . '/' . $this->getParameter('locale'));
    $outcomeForm = $this->createForm(ApplicationOutcomeType::class, $outcome, $options)->handleRequest($request);

    if ($outcomeForm->isSubmitted() && $outcomeForm->isValid()) {

      $allegatoOperatoreRepository = $this->getDoctrine()->getRepository(AllegatoOperatore::class);

      /** @var ApplicationOutcome $outcome */
      $outcome = $outcomeForm->getData();
      $pratica->setEsito($outcome->getOutcome());
      if ($outcome->getMessage() !== null) {
        $pratica->setMotivazioneEsito($outcome->getMessage());
      }
      foreach ($outcome->getAttachments() as $attachment) {
        if (isset($attachment['id'])) {
          $allegatoOperatore = $allegatoOperatoreRepository->findOneBy(['id' => $attachment['id']]);
          if ($allegatoOperatore instanceof AllegatoOperatore) {
            $pratica->addAllegatoOperatore($allegatoOperatore);
          }
        }
      }

      if ($pratica->getServizio()->isPaymentDeferred()) {
        $pratica->setPaymentAmount($outcome->getPaymentAmount());
      }

      try {
        $this->praticaManager->finalize($pratica, $user);
      } catch (\Exception $e) {
        $this->logger->error($e->getMessage() . ' --- ' . $e->getTraceAsString());
        $this->addFlash('error', $e->getMessage());
      }
      return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica]);
    }

    // Integration request
    $defaultData = [
      'message' => $this->translator->trans('operatori.richiedi_integrazioni_tpl', [
          '%user_name%' => $pratica->getUser()->getFullName(),
          '%servizio%' => $pratica->getServizio()->getName(),
        ]
      )
    ];

    $integrationRequestform = $this->createForm('App\Form\Rest\Transition\RequestIntegrationFormType', $defaultData);
    $integrationRequestform->handleRequest($request);
    if ($integrationRequestform->isSubmitted() && $integrationRequestform->isValid()) {

      $data = $integrationRequestform->getData();
      try {
        $this->praticaManager->requestIntegration($pratica, $this->getUser(), $data);
        $this->addFlash('success', $this->translator->trans('operatori.integration_succes'));
      } catch (\Exception $e) {
        $this->logger->error($e->getMessage() . ' --- ' . $e->getTraceAsString());
        $this->addFlash('error', $e->getMessage());
      }

      return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica]);
    }

    /** @var PraticaRepository $repository */
    $repository = $this->getDoctrine()->getRepository('App\Entity\Pratica');
    $praticheRecenti = $repository->findRecentlySubmittedPraticheByUser($pratica, $applicant, 5);

    $fiscalCode = null;
    if ($pratica->isFormIOType()) {
      /** @var Schema $schema */
      $schema = $this->schemaFactory->createFromFormId($pratica->getServizio()->getFormIoId());
      if (!empty($pratica->getDematerializedForms()['data'])) {
        $data = $schema->getDataBuilder()->setDataFromArray($pratica->getDematerializedForms()['data'])->toFullFilledFlatArray();
        if (isset($data['applicant.fiscal_code.fiscal_code'])) {
          $fiscalCode = $data['applicant.fiscal_code.fiscal_code'];
        }
      }
    } else {
      $fiscalCode = $applicant->getCodiceFiscale();
    }

    $moduleProtocols = [];
    $outcomeProtocols = [];

    foreach ($pratica->getNumeriProtocollo() as $protocollo) {
      if (Uuid::isValid($protocollo->id)) {
        $allegato = $this->entityManager->getRepository('App\Entity\Allegato')->find($protocollo->id);
      } else {
        $allegato = $this->entityManager->getRepository('App\Entity\Allegato')->findOneBy(['idDocumentoProtocollo' => $protocollo->id]);
      }
      if ($allegato instanceof Allegato) {
        $moduleProtocols[] = [
          'allegato' => $allegato,
          'tipo' => (new \ReflectionClass(get_class($allegato)))->getShortName(),
          'protocollo' => $protocollo->protocollo,
        ];
      }
    }
    if ($pratica->getRispostaOperatore()) {
      foreach ($pratica->getRispostaOperatore()->getNumeriProtocollo() as $protocollo) {
        $allegato = $this->entityManager->getRepository('App\Entity\Allegato')->find($protocollo->id);
        if ($allegato instanceof Allegato) {
          $outcomeProtocols[] = [
            'allegato' => $allegato,
            'tipo' => (new \ReflectionClass(get_class($allegato)))->getShortName(),
            'protocollo' => $protocollo->protocollo,
          ];
        }
      }
    }

    return $this->render('Operatori/showPratica.html.twig', [
      'pratiche_recenti' => $praticheRecenti,
      'applications_in_folder' => $repository->getApplicationsInFolder($pratica),
      'attachments_count' => $this->praticaManager->countAttachments($pratica),
      'messageAttachments' => $attachments,
      'messageForm' => $messageForm->createView(),
      'outcomeForm' => $outcomeForm->createView(),
      'integration_request_form' => $integrationRequestform->createView(),
      'pratica' => $pratica,
      'user' => $this->getUser(),
      'fiscal_code' => $fiscalCode,
      'formserver_url' => $this->getParameter('formserver_admin_url'),
      'tab' => $tab,
      'module_protocols' => $moduleProtocols,
      'outcome_protocols' => $outcomeProtocols,
      'token' => $this->JWTTokenManager->create($this->getUser()),
      'meetings' => $repository->findOrderedMeetings($pratica),
      'incoming_meetings' => $repository->findIncomingMeetings($pratica),
      'module_files' => $this->praticaManager->getGroupedModuleFiles($pratica),
      'last_owner_message' => $repository->getLastMessageByApplicationOwner($pratica)
    ]);
  }

  /**
   * @Route("/{pratica}/change-status",name="operatori_show_change_status")
   * @param Pratica|DematerializedFormPratica $pratica
   * @return RedirectResponse
   */
  public function changeStatusPraticaAction(Pratica $pratica, Request $request)
  {

    if (!in_array($request->request->get('status'), $pratica->getAllowedStates())) {
      $this->addFlash('error', $this->translator->trans('operatori.error_status_selected'));
      return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica]);
    }

    $newStatus = $request->request->get('status');

    /** @var OperatoreUser $user */
    $user = $this->getUser();
    $this->denyAccessUnlessGranted(ApplicationVoter::VIEW, $pratica);

    if ($pratica->getServizio()->isAllowReopening()) {
      try {

        if ($pratica->getEsito() !== null && $pratica->getMotivazioneEsito() !== null && $newStatus < Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE) {
          $pratica->setEsito(null);
          $pratica->setMotivazioneEsito(null);
          $pratica->removeRispostaOperatore();
        }

        // Todo: verificare
        /*if ($pratica->getOperatore() && $newStatus < Pratica::STATUS_PENDING) {
          $pratica->setOperatore(null);
        }*/

        if (!$pratica->getOperatore() && $newStatus >= Pratica::STATUS_PENDING) {
          $pratica->setOperatore($user);
        }

        $statusChange = new StatusChange();
        $statusChange->setEvento('Cambio stato pratica pratica');
        $statusChange->setOperatore($user->getFullName());

        $this->praticaStatusService->setNewStatus(
          $pratica,
          $newStatus,
          $statusChange,
          true
        );
        $this->addFlash('success', $this->translator->trans('operatori.change_status_success'));
      } catch (\Exception $e) {
        $this->logger->error($this->translator->trans('operatori.error_change_state_description') . ': ' . $pratica->getIdDocumentoProtocollo() . ' ' . $e->getMessage());
        $this->addFlash('error', $this->translator->trans('operatori.error_change_state'));
      }
    } else {
      $this->addFlash('error', $this->translator->trans('operatori.error_update_change_state'));
    }
    $this->entityManager->refresh($pratica);
    return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica]);
  }

  /**
   * @Route("/{pratica}/acceptIntegration",name="operatori_accept_integration")
   * @param Pratica|DematerializedFormPratica $pratica
   * @return array|RedirectResponse
   * @deprecated deprecated since version 1.9.0
   */
  public function acceptIntegrationAction(Pratica $pratica)
  {
    /** @var OperatoreUser $user */
    $user = $this->getUser();
    $this->denyAccessUnlessGranted(ApplicationVoter::VIEW, $pratica);

    if ($pratica->getStatus() === Pratica::STATUS_DRAFT_FOR_INTEGRATION) {
      try {
        $this->praticaManager->acceptIntegration($pratica, $user);
        $this->addFlash('success', $this->translator->trans('operatori.integration_accepted_success'));
      } catch (\Exception $e) {
        $this->addFlash('error', $this->translator->trans('operatori.integration_accepted_error'));
      }
    } else {
      $this->addFlash('error', $this->translator->trans('operatori.error_pratice_state'));
    }
    return $this->redirectToRoute('operatori_show_pratica', ['pratica' => $pratica]);
  }

  /**
   * @Route("/list",name="operatori_list_by_ente")
   * @Security("has_role('ROLE_OPERATORE_ADMIN')")
   * @return array
   */
  public function listOperatoriByEnteAction()
  {
    $operatoreRepo = $this->getDoctrine()->getRepository('App\Entity\OperatoreUser');
    $operatori = $operatoreRepo->findBy(
      [
        'ente' => $this->getUser()->getEnte(),
      ]
    );
    return $this->render('Operatori/listOperatoriByEnte.html.twig', [
      'operatori' => $operatori,
      'user' => $this->getUser(),
    ]);
  }

  /**
   * @return FormInterface
   */
  private function setupMessageForm(Pratica $pratica)
  {
    $message = new Message();
    $message->setApplication($pratica);
    $message->setAuthor($this->getUser());
    return $this->createForm('App\Form\ApplicationMessageType', $message);
  }

  private function populateSelectStatusServicesPratiche()
  {
    //Servizi, pratiche delle select di filtraggio
    $serviziPratiche = $this->entityManager->createQueryBuilder()
      ->select('s.name', 's.slug')
      ->from('App:Pratica', 'p')
      ->innerJoin('App:Servizio', 's', 'WITH', 's.id = p.servizio')
      ->distinct()
      ->getQuery()
      ->getResult();

    $sql = "SELECT DISTINCT(status) as status
            FROM pratica WHERE status > 1000 AND submission_time IS NOT NULL ORDER BY status ASC";
    try {
      $stmt = $this->entityManager->getConnection()->prepare($sql);
      $result = $stmt->executeQuery()->fetchAllAssociative();
    } catch (Exception $e) {
      $this->logger->error($e->getMessage());
      $result = [];
    }

    $status = [];
    foreach ($result as $valore) {
      $status[] = array(
        "status" => $valore['status'],
        "name" => $this->translator->trans('pratica.dettaglio.stato_' . $valore['status'])
      );
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
  public function metricheAction(Request $request)
  {
    $status = $request->get('status');
    $services = $request->get('services');
    $time = (int)$request->get('time');

    if ($time <= 180) {
      $timeSlot = "minute";
      $timeDiff = "- " . $time . " minutes";
    } elseif ($time <= 1440) {
      $timeSlot = "hour";
      $timeDiff = "- " . ($time / 60) . " hours";
    } else {
      $timeSlot = "day";
      $timeDiff = "- " . ($time / 60 / 24) . " days";
    }

    $timeZone = date_default_timezone_get();

    $calculateInterval = date('Y-m-d H:i:s', strtotime($timeDiff));

    $where = " WHERE p.status > 1000 AND TO_TIMESTAMP(p.submission_time) AT TIME ZONE '" . $timeZone . "' >= '" . $calculateInterval . "'" . "and p.submission_time IS NOT NULL";

    $sqlParams = [];
    if ($services && $services != 'all') {
      $where .= " AND s.slug = ?";
      $sqlParams [] = $services;
    }

    if ($status && $status != 'all') {
      $where .= " AND p.status =" . "'" . (int)$status . "'";
    }

    $sql = "SELECT COUNT(p.id), date_trunc('" . $timeSlot . "', TO_TIMESTAMP(p.submission_time) AT TIME ZONE '" . $timeZone . "') AS tslot, s.name
            FROM pratica AS p LEFT JOIN servizio AS s ON p.servizio_id = s.id" .
      $where .
      " GROUP BY s.name, tslot ORDER BY tslot ASC";

    try {
      $stmt = $this->entityManager->getConnection()->executeQuery($sql, $sqlParams);
      $result = $stmt->fetchAllAssociative();
    } catch (Exception $e) {
      $this->logger->error($e->getMessage());
      $result = [];
    }

    $categories = $series = $data = array();

    foreach ($result as $r) {
      if (!in_array($r['tslot'], $categories)) {
        $categories [] = $r['tslot'];
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
      $data['series'][] = $temp;
    }
    $data['categories'] = $categories;
    return new Response(json_encode($data), 200);

  }

  /**
   * @Route("/backoffice/{pratica}", name="save_backoffice_data")
   * @ParamConverter("pratica", class="App\Entity\Pratica")
   * @param Request $request
   * @param Pratica $pratica
   *
   * @return Response
   */
  public function saveBackofficeDataAction(Request $request, Pratica $pratica)
  {
    $service = $pratica->getServizio();
    $schema = null;
    $result = $this->formServerService->getFormSchema($service->getBackofficeFormId());
    if ($result['status'] == 'success') {
      $schema = $result['schema'];
    }

    $flatSchema = $this->praticaManager->arrayFlat($schema, true);
    $flatData = $this->praticaManager->arrayFlat($request->request);

    $data = [
      'data' => $request->request->all(),
      'flattened' => $flatData,
      'schema' => $flatSchema,
    ];

    try {
      $pratica->setBackofficeFormData($data);
      $this->entityManager->persist($pratica);
      $this->entityManager->flush();

      return new JsonResponse(['status' => 'ok']);

    } catch (\Exception $e) {
      return new JsonResponse(['status' => 'error'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * @Route("/analytics", name="analytics_index")
   * @return Response
   */
  public function analyticsAction()
  {
    $config = $this->getParameter('analytics');
    return $this->render('Analytics/index.html.twig', [
      'analytics_config' => \json_encode($config)
    ]);
  }
}
