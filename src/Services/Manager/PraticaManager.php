<?php


namespace App\Services\Manager;


use App\Entity\Allegato;
use App\Entity\AllegatoMessaggio;
use App\Entity\CPSUser;
use App\Entity\FormIO;
use App\Entity\Message;
use App\Entity\Pratica;
use App\Entity\PraticaRepository;
use App\Entity\RichiestaIntegrazioneDTO;
use App\Entity\RispostaIntegrazione;
use App\Entity\RispostaIntegrazioneRepository;
use App\Entity\Servizio;
use App\Entity\StatusChange;
use App\Entity\User;
use App\Entity\UserGroup;
use App\FormIO\ExpressionValidator;
use App\FormIO\Schema;
use App\FormIO\SchemaComponent;
use App\FormIO\SchemaFactoryInterface;
use App\Logging\LogConstants;
use App\Payment\Gateway\Bollo;
use App\Payment\Gateway\MyPay;
use App\Protocollo\ProtocolloEvents;
use App\Services\InstanceService;
use App\Services\ModuloPdfBuilderService;
use App\Services\PaymentService;
use App\Services\PraticaStatusService;
use App\Utils\UploadedBase64File;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\Exception\ValidatorException;

class PraticaManager
{

  // Mappa una chiave "human redable" su quelle pessime usate da formio
  const APPLICANT_KEYS = [
    'name' => 'applicant.data.completename.data.name',
    'surname' => 'applicant.data.completename.data.surname',
    'email' => 'applicant.data.email_address',
    'fiscal_code' => 'applicant.data.fiscal_code.data.fiscal_code'
  ];

  // Serve per mappare i dati dello schema con quelli dell'utente
  const APPLICATION_USER_MAP = [
    'applicant.completename.name' => 'getNome',
    'applicant.completename.surname' => 'getCognome',
    'applicant.Born.natoAIl' => 'getDataNascita',
    'applicant.Born.place_of_birth' => 'getLuogoNascita',
    'applicant.fiscal_code.fiscal_code' => 'getCodiceFiscale',
    'applicant.address.address' => 'getIndirizzoResidenza',
    'applicant.address.house_number' => '',
    'applicant.address.municipality' => 'getCittaResidenza',
    'applicant.address.postal_code' => 'getCapResidenza',
    'applicant.address.county' => 'getProvinciaResidenza',
    'applicant.email_address' => 'getEmail',
    'applicant.email_repeat' => 'getEmail',
    'applicant.cell_number' => 'getCellulare',
    'applicant.phone_number' => 'getTelefono',
    'applicant.gender.gender' => 'getSessoAsString',
    'cell_number' => 'getCellulare',
  ];

  /**
   * @var
   */
  private $schema = null;

  /**
   * @var ModuloPdfBuilderService
   */
  private $moduloPdfBuilderService;
  /**
   * @var PraticaStatusService
   */
  private $praticaStatusService;
  /**
   * @var LoggerInterface
   */
  private $logger;
  /**
   * @var EntityManagerInterface
   */
  private $entityManager;

  /**
   * @var InstanceService
   */
  private $is;

  /**
   * @var TranslatorInterface
   */
  private $translator;

  /**
   * @var SchemaFactoryInterface
   */
  private $schemaFactory;
  /**
   * @var MessageManager
   */
  private $messageManager;
  /**
   * @var PaymentService
   */
  private $paymentService;

  /** @var ExpressionValidator */
  private $expressionValidator;

  /**
   * PraticaManagerService constructor.
   * @param EntityManagerInterface $entityManager
   * @param InstanceService $instanceService
   * @param ModuloPdfBuilderService $moduloPdfBuilderService
   * @param PraticaStatusService $praticaStatusService
   * @param TranslatorInterface $translator
   * @param LoggerInterface $logger
   * @param SchemaFactoryInterface $schemaFactory
   * @param MessageManager $messageManager ,
   * @param PaymentService $paymentService ,
   * @param ExpressionValidator $expressionValidator
   */
  public function __construct(
    EntityManagerInterface  $entityManager,
    InstanceService         $instanceService,
    ModuloPdfBuilderService $moduloPdfBuilderService,
    PraticaStatusService    $praticaStatusService,
    TranslatorInterface     $translator,
    LoggerInterface         $logger,
    SchemaFactoryInterface  $schemaFactory,
    MessageManager          $messageManager,
    PaymentService          $paymentService,
    ExpressionValidator     $expressionValidator
  )
  {
    $this->moduloPdfBuilderService = $moduloPdfBuilderService;
    $this->praticaStatusService = $praticaStatusService;
    $this->logger = $logger;
    $this->entityManager = $entityManager;
    $this->is = $instanceService;
    $this->translator = $translator;
    $this->schemaFactory = $schemaFactory;
    $this->messageManager = $messageManager;
    $this->paymentService = $paymentService;
    $this->expressionValidator = $expressionValidator;
  }

  /**
   * @return mixed
   */
  public function getSchema()
  {
    return $this->schema;
  }

  /**
   * @param mixed $schema
   */
  public function setSchema($schema): void
  {
    $this->schema = $schema;
  }

  /**
   * @param Pratica $pratica
   * @throws Exception
   */
  public function finalizeSubmission(Pratica $pratica)
  {

    /** @var PraticaRepository $repo */
    $repo = $this->entityManager->getRepository(Pratica::class);

    // Per non sovrascrivere comportamento in formio flow
    if ($pratica->getFolderId() == null) {
      $pratica->setServiceGroup($pratica->getServizio()->getServiceGroup());
      $pratica->setFolderId($repo->getFolderForApplication($pratica));
    }

    if ($pratica->getStatus() == Pratica::STATUS_DRAFT) {

      $pratica->setSubmissionTime(time());
      $this->praticaStatusService->setNewStatus($pratica, Pratica::STATUS_PRE_SUBMIT);

    } elseif ($pratica->getStatus() == Pratica::STATUS_DRAFT_FOR_INTEGRATION) {

      // Creo il file principale per le integrazioni
      $integrationsAnswer = $this->moduloPdfBuilderService->creaModuloProtocollabilePerRispostaIntegrazione($pratica);
      $pratica->addAllegato($integrationsAnswer);
      $this->praticaStatusService->setNewStatus($pratica, Pratica::STATUS_SUBMITTED_AFTER_INTEGRATION);
    }
  }

  /**
   * @param Pratica $pratica
   * @param User $user
   * @throws Exception
   */
  public function finalizePaymentCompleteSubmission(Pratica $pratica, User $user, $message = null)
  {
    $statusChange = new StatusChange();
    $statusChange->setEvento('Pagamento completato');
    $statusChange->setOperatore($user->getFullName());
    $this->praticaStatusService->setNewStatus(
      $pratica,
      Pratica::STATUS_PAYMENT_SUCCESS,
      $statusChange
    );

    if ($message['message'] !== null) {
      $this->generateStatusMessage($pratica, $message['message'], $message['subject'], [], $message['visibility']);
    }

  }

  /**
   * @param Pratica $pratica
   * @param User|null $user
   * @param UserGroup|null $userGroup
   * @throws Exception
   */
  public function assign(Pratica $pratica, User $user = null, UserGroup $userGroup = null)
  {
    if ($user && $pratica->getOperatore() && $pratica->getOperatore()->getFullName() === $user->getFullName() && $pratica->getUserGroup() === $userGroup) {
      throw new BadRequestHttpException(
        $this->translator->trans('pratica.already_assigned', ['%operator_fullname%' => $pratica->getOperatore()->getFullName()])
      );
    }

    if ($pratica->getServizio()->isProtocolRequired() && $pratica->getNumeroProtocollo() === null) {
      throw new BadRequestHttpException($this->translator->trans('pratica.no_protocol_number'));
    }

    if (!$user && !$userGroup) {
      throw new BadRequestHttpException($this->translator->trans('pratica.user_or_user_group_required_for_assignment'));
    }


    if ($user && $userGroup && !$user->getUserGroups()->contains($userGroup)) {
      throw new BadRequestHttpException($this->translator->trans('operatori.user_not_in_user_group', [
        '%fullname%' => $user->getFullName(),
        '%user_group%' => $userGroup->getName()
      ]));
    }

    // Assegnazioni automatiche del gruppo
    if ($user && !$userGroup) {
      if ($pratica->getUserGroup() && $pratica->getUserGroup()->getUsers()->contains($user)) {
        // Se non è specificato il gruppo tra i parametri, ma l'operatore appartiena al gruppo a cui è assegnata
        // la pratica mantengo l'assegnazione del gruppo
        $userGroup = $pratica->getUserGroup();
      } else {
        // Ricerco tutti gli uffici a cui appartiene l'operatore
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('user_group')
          ->from('App:UserGroup', 'user_group')
          ->andWhere(':user MEMBER OF user_group.users')
          ->setParameter('user', $user)
          ->orderBy('user_group.name', 'ASC');

        $userGroups = $qb->getQuery()->getResult();
        $preferredUserGroup = null;

        if (!empty($userGroups)) {
          // Fixme: inserire nell'ordinamento della query precendente: attenzione però perche la pratica può essere
          // associata ad un ufficio che non è incaricato del servizio
          foreach ($userGroups as $userGroup) {
            if (!$preferredUserGroup && $userGroup->getServices()->contains($pratica->getServizio())) {
              // Utilizzo il primo uffico in ordine alfabetico che sia associato al servizio della pratica
              $preferredUserGroup = $userGroup;
            }
          }

          // Se nessun ufficio gestisce il servizio allora prendo il primo in ordine alfabetico
          $userGroup = $preferredUserGroup ?? reset($userGroups);
        }
      }
    }

    $pratica->setOperatore($user);
    $pratica->setUserGroup($userGroup);

    $statusChange = new StatusChange();
    $statusChange->setEvento('Presa in carico');

    if ($user) {
      $statusChange->setOperatore($user->getFullName());
    }

    if ($userGroup) {
      $statusChange->setUserGroup($userGroup->getName());
    }

    try {
      $this->praticaStatusService->validateChangeStatus($pratica, Pratica::STATUS_PENDING);
      $this->praticaStatusService->setNewStatus($pratica, Pratica::STATUS_PENDING, $statusChange);
    } catch (\Exception $e) {
      $this->praticaStatusService->setNewStatus($pratica, $pratica->getStatus(), $statusChange);
    }

    $this->logger->info(
      LogConstants::PRATICA_ASSIGNED,
      [
        'pratica' => $pratica->getId(),
        'user' => $pratica->getUser()->getId(),
      ]
    );
  }


  /**
   * @param Pratica $pratica
   * @param User $user
   * @throws Exception
   */
  public function finalize(Pratica $pratica, User $user)
  {
    if ($pratica->getStatus() == Pratica::STATUS_COMPLETE
      || $pratica->getStatus() == Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE
      || $pratica->getStatus() == Pratica::STATUS_CANCELLED
      || $pratica->getStatus() == Pratica::STATUS_CANCELLED_WAITALLEGATIOPERATORE) {
      throw new BadRequestHttpException($this->translator->trans('pratica.already_processed'));
    }

    if ($pratica->getRispostaOperatore() == null) {
      $signedResponse = $this->moduloPdfBuilderService->createSignedResponseForPratica($pratica);
      $pratica->addRispostaOperatore($signedResponse);
    }

    $protocolloIsRequired = $pratica->getServizio()->isProtocolRequired();
    $statusChange = new StatusChange();
    $statusChange->setOperatore($user->getFullName());

    if ($pratica->getEsito()) {
      $statusChange->setEvento('Approvazione pratica');
      $statusChange->setOperatore($user->getFullName());

      if ($pratica->getServizio()->isPaymentDeferred() && $pratica->getPaymentAmount() > 0) {
        // Seleziono il primo gateway disponibile
        $selectedGateways = $pratica->getServizio()->getPaymentParameters()['gateways'] ?? [];
        if (!$selectedGateways) {
          throw new BadRequestHttpException($this->translator->trans('payment.no_selected_gateways'));
        }
        $identifier = array_keys($selectedGateways)[0];

        if (!in_array($identifier, [Bollo::IDENTIFIER, MyPay::IDENTIFIER])) {
          // Mantengo la logica precedente
          $pratica->setPaymentData($this->paymentService->createPaymentData($pratica));
          $pratica->setPaymentType($identifier);
        }

        $this->praticaStatusService->setNewStatus(
          $pratica,
          Pratica::STATUS_PAYMENT_PENDING,
          $statusChange
        );
      } else {

        if ($protocolloIsRequired) {
          $this->praticaStatusService->setNewStatus(
            $pratica,
            Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE,
            $statusChange
          );
        } else {
          $this->praticaStatusService->setNewStatus(
            $pratica,
            Pratica::STATUS_COMPLETE,
            $statusChange
          );
        }
      }

      $this->logger->info(
        LogConstants::PRATICA_APPROVED,
        [
          'pratica' => $pratica->getId(),
          'user' => $pratica->getUser()->getId(),
        ]
      );
    } else {

      $statusChange->setEvento('Rifiuto pratica');
      $statusChange->setOperatore($user->getFullName());

      if ($protocolloIsRequired) {
        $this->praticaStatusService->setNewStatus(
          $pratica,
          Pratica::STATUS_CANCELLED_WAITALLEGATIOPERATORE,
          $statusChange
        );
      } else {
        $this->praticaStatusService->setNewStatus(
          $pratica,
          Pratica::STATUS_CANCELLED,
          $statusChange
        );
      }

      $this->logger->info(
        LogConstants::PRATICA_CANCELLED,
        [
          'pratica' => $pratica->getId(),
          'user' => $pratica->getUser()->getId(),
        ]
      );
    }
  }

  /**
   * @param Pratica $pratica
   * @param User $user
   * @throws Exception
   */
  public function withdrawApplication(Pratica $pratica, User $user)
  {

    if ($pratica->getStatus() == Pratica::STATUS_WITHDRAW) {
      throw new BadRequestHttpException($this->translator->trans('pratica.already_processed'));
    }

    if ($pratica->getWithdrawAttachment() == null) {
      $withdrawAttachment = $this->moduloPdfBuilderService->createWithdrawForPratica($pratica);
      $pratica->addAllegato($withdrawAttachment);
    }

    $statusChange = new StatusChange();
    $this->praticaStatusService->setNewStatus(
      $pratica,
      Pratica::STATUS_WITHDRAW,
      $statusChange
    );

    $this->logger->info(
      LogConstants::PRATICA_WITHDRAW,
      [
        'pratica' => $pratica->getId(),
        'user' => $pratica->getUser()->getId(),
      ]
    );
  }

  /**
   * @param Pratica $pratica
   * @param User $user
   * @param $data
   * @throws Exception
   */
  public function requestIntegration(Pratica $pratica, User $user, $data)
  {

    $this->praticaStatusService->validateChangeStatus($pratica, Pratica::STATUS_REQUEST_INTEGRATION);

    $message = new Message();
    $message->setApplication($pratica);
    $message->setProtocolRequired(false);
    $message->setVisibility(Message::VISIBILITY_APPLICANT);
    $message->setMessage($data['message']);
    $message->setSubject($this->translator->trans('pratica.messaggi.oggetto', ['%pratica%' => $message->getApplication()]));
    $message->setAuthor($user);

    $requestAttachmentsIds = $requestAttachments = [];
    foreach ($data['attachments'] as $attachment) {
      $base64Content = $attachment->getFile();
      $file = new UploadedBase64File($base64Content, $attachment->getMimeType(), $attachment->getName());
      $allegato = new AllegatoMessaggio();
      $allegato->setFile($file);
      $allegato->setOwner($pratica->getUser());
      $allegato->setDescription('Allegato richiesta integrazione');
      $allegato->setOriginalFilename($attachment->getName());
      //$allegato->setIdRichiestaIntegrazione($integration->getId());
      $this->entityManager->persist($allegato);
      $message->addAttachment($allegato);
      $requestAttachments[] = $allegato;
      $requestAttachmentsIds[] = $allegato->getId();
    }
    $this->messageManager->save($message);

    // Creo il file di richiesta integrazione
    $richiestaIntegrazione = new RichiestaIntegrazioneDTO([], null, $data['message']);
    $this->praticaStatusService->validateChangeStatus($pratica, Pratica::STATUS_REQUEST_INTEGRATION);
    $integration = $this->moduloPdfBuilderService->creaModuloProtocollabilePerRichiestaIntegrazione(
      $pratica,
      $richiestaIntegrazione,
      $requestAttachments
    );
    if (!empty($requestAttachmentsIds)) {
      $integration->setAttachments($requestAttachmentsIds);
      $this->entityManager->persist($integration);
    }
    $pratica->addRichiestaIntegrazione($integration);


    $this->entityManager->persist($pratica);
    $this->entityManager->flush();

    $statusChange = new StatusChange();
    $statusChange->setOperatore($user->getFullName());
    $statusChange->setMessageId($message->getId());
    $this->praticaStatusService->setNewStatus($pratica, Pratica::STATUS_REQUEST_INTEGRATION, $statusChange);
  }

  /**
   * @param Pratica $pratica
   * @param User $user
   * @param $messages
   * @return void
   * @throws \League\Flysystem\FileExistsException
   * @throws Exception
   */
  public function cancelIntegration(Pratica $pratica, User $user)
  {
    $this->praticaStatusService->validateChangeStatus($pratica, Pratica::STATUS_SUBMITTED_AFTER_INTEGRATION);
    $integrationsAnswer = $this->moduloPdfBuilderService->creaModuloProtocollabilePerRispostaIntegrazione(
      $pratica,
      [],
      true
    );
    $pratica->addAllegato($integrationsAnswer);

    if (!$user instanceof CPSUser) {
      $statusChange = new StatusChange();
      $statusChange->setOperatore($user->getFullName());
    }
    $this->praticaStatusService->setNewStatus($pratica, Pratica::STATUS_SUBMITTED_AFTER_INTEGRATION, $statusChange);
  }

  /**
   * @param Pratica $pratica
   * @param User $user
   * @param $messages
   * @return void
   * @throws \League\Flysystem\FileExistsException
   * @throws Exception
   */
  public function acceptIntegration(Pratica $pratica, User $user, $messages = null)
  {
    $this->praticaStatusService->validateChangeStatus($pratica, Pratica::STATUS_SUBMITTED_AFTER_INTEGRATION);
    $integrationsAnswer = $this->moduloPdfBuilderService->creaModuloProtocollabilePerRispostaIntegrazione(
      $pratica,
      $messages
    );
    $pratica->addAllegato($integrationsAnswer);

    if (!$user instanceof CPSUser) {
      $statusChange = new StatusChange();
      $statusChange->setOperatore($user->getFullName());
    }
    $this->praticaStatusService->setNewStatus($pratica, Pratica::STATUS_SUBMITTED_AFTER_INTEGRATION, $statusChange);
  }

  /**
   * @param Pratica $pratica
   * @param User $user
   * @param $data
   * @throws Exception
   */
  public function registerIntegrationRequest(Pratica $pratica, User $user, $data)
  {
    $this->praticaStatusService->validateChangeStatus($pratica, Pratica::STATUS_DRAFT_FOR_INTEGRATION);
    $integrationRequest = $pratica->getRichiestaDiIntegrazioneAttiva();
    $integrationRequest->setNumeroProtocollo($data['integration_outbound_protocol_number']);
    $integrationRequest->setIdDocumentoProtocollo($data['integration_outbound_protocol_document_id']);
    $this->entityManager->persist($integrationRequest);
    $this->entityManager->flush();

    if (!$user instanceof CPSUser) {
      $statusChange = new StatusChange();
      $statusChange->setOperatore($user->getFullName());
    }
    $this->praticaStatusService->setNewStatus($pratica, Pratica::STATUS_DRAFT_FOR_INTEGRATION, $statusChange);
  }

  /**
   * @param Pratica $pratica
   * @param User $user
   * @param $data
   * @throws Exception
   */
  public function registerIntegrationAnswer(Pratica $pratica, User $user, $data)
  {
    $this->praticaStatusService->validateChangeStatus($pratica, Pratica::STATUS_REGISTERED_AFTER_INTEGRATION);

    $integrationAnswer = $this->getIntegrationAnswer($pratica);

    if ($integrationAnswer instanceof RispostaIntegrazione) {


      $integrationAnswer->setNumeroProtocollo($data['integration_inbound_protocol_number']);
      $integrationAnswer->setIdDocumentoProtocollo($data['integration_inbound_protocol_document_id']);

      $this->entityManager->persist($integrationAnswer);
      $this->entityManager->flush();

      if (!$user instanceof CPSUser) {
        $statusChange = new StatusChange();
        $statusChange->setOperatore($user->getFullName());
      }
      $this->praticaStatusService->setNewStatus($pratica, Pratica::STATUS_REGISTERED_AFTER_INTEGRATION, $statusChange);
    }
  }

  /**
   * @param Pratica $pratica
   * @return RispostaIntegrazione|null
   */
  public function getIntegrationAnswer(Pratica $pratica)
  {

    $integrationRequest = $pratica->getRichiestaDiIntegrazioneAttiva();

    /** @var RispostaIntegrazioneRepository $integrationAnswerRepo */
    $integrationAnswerRepo = $this->entityManager->getRepository('App\Entity\RispostaIntegrazione');

    $integrationAnswerCollection = $integrationAnswerRepo->findByIntegrationRequest($integrationRequest->getId());

    if (!empty($integrationAnswerCollection)) {
      /** @var RispostaIntegrazione $answer */
      return $integrationAnswerCollection[0];
    }

    return null;
  }

  /**
   * @param Pratica $pratica
   * @param string $text
   * @param string $subject
   * @return Message
   */
  public function generateStatusMessage(
    Pratica $pratica,
    string  $text,
    string  $subject,
    array   $callToActions = [],
            $visibility = Message::VISIBILITY_APPLICANT
  ): Message
  {
    $message = new Message();
    $message->setApplication($pratica);
    $message->setProtocolRequired(false);
    $message->setVisibility($visibility);
    $message->setMessage($text);
    $message->setSubject($subject);
    $message->setCallToAction($callToActions);
    $message->setEmail($pratica->getUser()->getEmailContatto());
    $message->setSentAt(time());

    $this->entityManager->persist($message);
    $this->entityManager->persist($pratica);
    $this->entityManager->flush();

    return $message;
  }

  public function createDraftApplication(Servizio $servizio, CPSUser $user, array $additionalDematerializedData)
  {
    $pratica = new FormIO();
    $pratica->setUser($user);
    $pratica->setServizio($servizio);
    $pratica->setStatus(Pratica::STATUS_DRAFT);
    $pratica->setEnte($this->is->getCurrentInstance());

    $cpsUserData = [
      'applicant' => [
        'data' => [
          'completename' => [
            'data' => [
              'name' => $user->getNome(),
              'surname' => $user->getCognome(),
            ],
          ],
          'gender' => [
            'data' => [
              'gender' => $user->getSessoAsString(),
            ],
          ],
          'Born' => [
            'data' => [
              'natoAIl' => $user->getDataNascita()->format('d/m/Y'),
              'place_of_birth' => $user->getLuogoNascita(),
            ],
          ],
          'fiscal_code' => [
            'data' => [
              'fiscal_code' => $user->getCodiceFiscale(),
            ],
          ],
          'address' => [
            'data' => [
              'address' => $user->getIndirizzoResidenza(),
              'house_number' => '',
              'municipality' => $user->getCittaResidenza(),
              'postal_code' => $user->getCapResidenza(),
              'county' => $user->getProvinciaResidenza(),
            ],
          ],
          'email_address' => $user->getEmail(),
          'email_repeat' => $user->getEmail(),
          'cell_number' => $user->getCellulare(),
          'phone_number' => $user->getTelefono(),
        ],
      ],
      'cell_number' => $user->getCellulare(),
      'phone_number' => $user->getTelefono(),
    ];

    $pratica->setDematerializedForms([
      "data" => array_merge(
        $additionalDematerializedData,
        $cpsUserData
      ),
    ]);

    $this->entityManager->persist($pratica);
    $this->entityManager->flush();

    return $pratica;
  }

  /**
   * @param $array
   * @param bool $isSchema
   * @param string $prefix
   * @return array
   */
  public function arrayFlat($array, $isSchema = false, $prefix = '')
  {
    $result = array();
    foreach ($array as $key => $value) {

      if ($key === 'metadata' || $key === 'state') {
        continue;
      }

      $isFile = false;
      if (!$isSchema && isset($this->schema[$key]['type']) &&
        ($this->schema[$key]['type'] == 'file' || $this->schema[$key]['type'] == 'sdcfile' || $this->schema[$key]['type'] == 'financial_report')) {
        $isFile = true;
      }
      $new_key = $prefix . (empty($prefix) ? '' : '.') . $key;

      if (is_array($value) && !$isFile) {
        $result = array_merge($result, $this->arrayFlat($value, $isSchema, $new_key));
      } else {
        $result[$new_key] = $value;
      }
    }

    return $result;
  }

  /**
   * @param array $data
   * @return CPSUser
   */
  public function checkUser(array $data): CPSUser
  {
    $cf = $data['flattened']['applicant.data.fiscal_code.data.fiscal_code'] ?? false;

    $user = null;
    if ($cf) {
      $qb = $this->entityManager->createQueryBuilder()
        ->select('u')
        ->from('App:CPSUser', 'u')
        ->andWhere('UPPER(u.username) = :username')
        ->setParameter('username', strtoupper($cf));
      try {
        $user = $qb->getQuery()->getSingleResult();
      } catch (\Exception $e) {
      }
    }

    if (!$user instanceof CPSUser) {
      $birthDay = null;
      if (isset($data['flattened']['applicant.data.Born.data.natoAIl']) && !empty($data['flattened']['applicant.data.Born.data.natoAIl'])) {
        $birthDay = DateTime::createFromFormat('d/m/Y', $data['flattened']['applicant.data.Born.data.natoAIl']);
      }
      $user = new CPSUser();
      $user
        ->setUsername($cf)
        ->setCodiceFiscale($cf)
        ->setSessoAsString($data['flattened']['applicant.gender.gender'] ?? '')
        ->setCellulareContatto($data['flattened']['applicant.data.cell_number'] ?? '')
        ->setCpsTelefono($data['flattened']['applicant.data.phone_number'] ?? '')
        ->setEmail($data['flattened']['applicant.data.email_address'] ?? $user->getId() . '@' . CPSUser::FAKE_EMAIL_DOMAIN)
        ->setEmailContatto(
          $data['flattened']['applicant.data.email_address'] ?? $user->getId() . '@' . CPSUser::FAKE_EMAIL_DOMAIN
        )
        ->setNome($data['flattened']['applicant.data.completename.data.name'] ?? '')
        ->setCognome($data['flattened']['applicant.data.completename.data.surname'] ?? '')
        ->setDataNascita($birthDay)
        ->setLuogoNascita(
          isset($data['flattened']['applicant.data.Born.data.place_of_birth']) && !empty($data['flattened']['applicant.data.Born.data.place_of_birth']) ? $data['flattened']['applicant.data.Born.data.place_of_birth'] : ''
        )
        ->setSdcIndirizzoResidenza(
          isset($data['flattened']['applicant.data.address.data.address']) && !empty($data['flattened']['applicant.data.address.data.address']) ? $data['flattened']['applicant.data.address.data.address'] : ''
        )
        ->setSdcCittaResidenza(
          isset($data['flattened']['applicant.data.address.data.municipality']) && !empty($data['flattened']['applicant.data.address.data.municipality']) ? $data['flattened']['applicant.data.address.data.municipality'] : ''
        )
        ->setSdcCapResidenza(
          isset($data['flattened']['applicant.data.address.data.postal_code']) && !empty($data['flattened']['applicant.data.address.data.postal_code']) ? $data['flattened']['applicant.data.address.data.postal_code'] : ''
        )
        ->setSdcProvinciaResidenza(
          isset($data['flattened']['applicant.data.address.data.county']) && !empty($data['flattened']['applicant.data.address.data.county']) ? $data['flattened']['applicant.data.address.data.county'] : ''
        );

      $user->addRole('ROLE_USER')
        ->addRole('ROLE_CPS_USER')
        ->setEnabled(true)
        ->setPassword('');

      $this->entityManager->persist($user);
    }

    return $user;
  }

  /**
   * @param array $data
   * @param CPSUser $user
   * @param null $applicationId
   * @throws Exception
   */
  public function validateUserData(array $data, CPSUser $user, $applicationId = null)
  {
    if (strcasecmp($data['applicant.data.fiscal_code.data.fiscal_code'], $user->getCodiceFiscale()) != 0) {
      $this->logger->error("Fiscal code Mismatch", [
          'pratica' => $applicationId ?? '-',
          'cps' => $user->getCodiceFiscale(),
          'form' => $data['applicant.data.fiscal_code.data.fiscal_code'],
        ]
      );
      throw new Exception($this->translator->trans('steps.formio.fiscalcode_violation_message'));
    }

    if (strcasecmp($data['applicant.data.completename.data.name'], $user->getNome()) != 0) {
      $this->logger->error("Name Mismatch", [
          'pratica' => $applicationId ?? '-',
          'cps' => $user->getCodiceFiscale(),
          'form' => $data['applicant.data.completename.data.name'],
        ]
      );
      throw new Exception($this->translator->trans('steps.formio.name_violation_message'));
    }

    if (strcasecmp($data['applicant.data.completename.data.surname'], $user->getCognome()) != 0) {
      $this->logger->error("Surname Mismatch", [
          'pratica' => $applicationId ?? '-',
          'cps' => $user->getCodiceFiscale(),
          'form' => $data['applicant.data.completename.data.surname'],
        ]
      );
      throw new Exception($this->translator->trans('steps.formio.surname_violation_message'));
    }
  }

  /**
   * @param array $data
   * @throws Exception
   */
  public function validateDematerializedData(array $data, Pratica $pratica)
  {
    if (!$data['data'] || !$data['flattened']) {
      $this->logger->error("Received empty dematerialized data");
      throw new ValidatorException($this->translator->trans('steps.formio.empty_data_violation_message'));
    }

    $errors = $this->expressionValidator->validateData($pratica->getServizio(), json_encode($data['data']));
    if (!empty($errors)) {
      $this->logger->error("Received duplcated unique_id");
      throw new ValidatorException($this->translator->trans('steps.formio.duplicated_unique_id'));
    }
  }

  /**
   * @param Schema $schema
   * @param CPSUser $user
   * @return mixed
   */
  public function getMappedFormDataWithUserData(Schema $schema, CPSUser $user)
  {
    $data = $schema->getDataBuilder();
    if ($schema->hasComponents()) {
      foreach (self::APPLICATION_USER_MAP as $schemaFlatName => $userMethod) {
        try {
          if ($schema->hasComponent($schemaFlatName) && method_exists($user, $userMethod)) {
            $component = $schema->getComponent($schemaFlatName);
            $value = $user->{$userMethod}();
            // se il campo è datatime popola con iso8601 altrimenti testo
            if ($value instanceof DateTime) {
              if ($component['form_type'] == DateTimeType::class) {
                $value = $value->format(DateTime::ISO8601);
              } else {
                $value = $value->format('d/m/Y');
              }
            }
            if ($component['form_type'] == ChoiceType::class
              && isset($component['form_options']['choices'])
              && !empty($component['form_options']['choices'])) {
              if ($schemaFlatName !== 'applicant.gender.gender') {
                $value = strtoupper($value);
              }
              if (!in_array($value, $component['form_options']['choices'])) {
                $value = null;
              }
            }
            if ($value) {
              $data->set($schemaFlatName, $value);
            }
          }
        } catch (\Exception $e) {
          $this->logger->error($e->getMessage());
        }
      }
    }

    return $data->toArray();
  }

  /**
   * @param Pratica $pratica
   * @param $flattenedData
   * @throws Exception
   */
  public function addAttachmentsToApplication(Pratica $pratica, $flattenedData)
  {
    $currentAttachments = $pratica->getAllegati();
    $attachments = [];
    foreach ($flattenedData as $key => $value) {
      // Associa gli allegati alla pratica
      if (isset($this->schema[$key]['type']) && ($this->schema[$key]['type'] == 'file' || $this->schema[$key]['type'] == 'sdcfile')) {
        foreach ($value as $file) {
          $id = $file['data']['id'];
          $attachment = $this->entityManager->getRepository('App\Entity\Allegato')->find($id);
          if ($attachment instanceof Allegato) {
            if (isset($file['fileType']) && !empty($file['fileType'])) {
              $attachment->setDescription($file['fileType']);
              $this->entityManager->persist($attachment);
            }

            // Imposto il proprietario per gli allegati appena creati se non presente
            if (!$attachment->getOwner() instanceof CPSUser) {
              $attachment->setOwner($pratica->getUser());
              $this->entityManager->persist($attachment);
            }

            $attachments[] = $id;
            $pratica->addAllegato($attachment);
          } else {
            $msg = "The file present in form schema doesn't exist in database";
            $this->logger->error($msg, ['pratica' => $pratica->getId(), 'allegato' => $id]);
            throw new \Exception($msg);
          }
        }
      }
    }

    // Rimuovo gli allegati che non sono più presenti nella pratica
    foreach ($currentAttachments as $attachment) {
      if (!in_array($attachment->getId(), $attachments)) {
        $pratica->removeAllegato($attachment);
      }
    }

    // Verifico che il numero degli allegati associati alla pratica sia uguale a quello passato nel form
    if ($pratica->getAllegati()->count() != count($attachments)) {
      $msg = 'The number of files in form data is not equal to those linked to the application';
      $this->logger->error($msg, ['pratica' => $pratica->getId()]);
      throw new \Exception($msg);
    }
  }

  /**
   * @param Pratica $pratica
   * @return int
   */
  public function countAttachments(Pratica $pratica)
  {

    /*
      pratica.moduliCompilati.count + pratica.allegati.count + messageAttachments|length + pratica.allegatiOperatore.count +
      pratica.richiesteIntegrazione|length + pratica.integrationAnswers|length + risposta
    */

    $count = 0;
    $count += $pratica->getModuliCompilati()->count();
    // Include sia allegati che che risposte ad integrazione
    $count += $pratica->getAllegati()->count();

    $count += $pratica->getRichiesteIntegrazione()->count();

    if ($pratica->getRispostaOperatore()) {
      $count++;
    }
    $count += $pratica->getAllegatiOperatore()->count();

    return $count;
  }

  /**
   * @param Pratica $pratica
   * @return array
   */
  public function getGroupedModuleFiles(Pratica $pratica): array
  {
    $files = [];
    if ($pratica->getServizio()->isLegacy()) {
      return $files;
    }
    $attachments = $pratica->getAllegatiWithIndex();
    $schema = $this->schemaFactory->createFromFormId($pratica->getServizio()->getFormIoId());
    $filesComponents = $schema->getFileComponents();
    $data = $pratica->getDematerializedForms();

    /** @var SchemaComponent $component */
    foreach ($filesComponents as $component) {
      if (isset($data['flattened'][$component->getName()])) {
        $componentOptions = $component->getFormOptions();
        $labelParts = explode('/', $componentOptions['label']);
        $page = $labelParts[0];
        unset($labelParts[0]);
        $label = implode(' / ', $labelParts);
        //$files [$labelParts[0]][end($labelParts)][]= Application::prepareFormioFile($data['flattened'][$component->getName()]);
        foreach ($data['flattened'][$component->getName()] as $f) {
          $id = $f['data']['id'];
          $files [$page][$label][] = $attachments[$id];
        }
      }
    }

    return $files;
  }

  public function getUserEmail(array $data, CPSUser $user)
  {
    return $data['flattened']['applicant.data.email_address'] ?? $data['flattened']['email_address']
      ?? $data['flattened']['email'] ?? $user->getId() . '@' . User::ANONYMOUS_FAKE_EMAIL_DOMAIN;
  }
}
