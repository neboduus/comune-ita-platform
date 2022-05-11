<?php


namespace AppBundle\Services\Manager;


use AppBundle\Dto\Application;
use AppBundle\Entity\Allegato;
use AppBundle\Entity\AllegatoMessaggio;
use AppBundle\Entity\CPSUser;
use AppBundle\Entity\FormIO;
use AppBundle\Entity\Message;
use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\PraticaRepository;
use AppBundle\Entity\RichiestaIntegrazioneDTO;
use AppBundle\Entity\RispostaIntegrazione;
use AppBundle\Entity\RispostaIntegrazioneRepository;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\StatusChange;
use AppBundle\Entity\User;
use AppBundle\Event\DispatchEmailFromMessageEvent;
use AppBundle\Event\ProtocollaPraticaSuccessEvent;
use AppBundle\Form\FormIO\FormIORenderType;
use AppBundle\FormIO\Schema;
use AppBundle\FormIO\SchemaComponent;
use AppBundle\FormIO\SchemaFactoryInterface;
use AppBundle\Logging\LogConstants;
use AppBundle\Protocollo\ProtocolloEvents;
use AppBundle\Services\InstanceService;
use AppBundle\Services\ModuloPdfBuilderService;
use AppBundle\Services\PraticaStatusService;
use AppBundle\Utils\UploadedBase64File;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\ORMException;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Translation\TranslatorInterface;

class PraticaManager
{

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
    'cell_number' => 'getCellulare'
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
   * PraticaManagerService constructor.
   * @param EntityManagerInterface $entityManager
   * @param InstanceService $instanceService
   * @param ModuloPdfBuilderService $moduloPdfBuilderService
   * @param PraticaStatusService $praticaStatusService
   * @param TranslatorInterface $translator
   * @param LoggerInterface $logger
   * @param SchemaFactoryInterface $schemaFactory
   */
  public function __construct(
    EntityManagerInterface $entityManager,
    InstanceService $instanceService,
    ModuloPdfBuilderService $moduloPdfBuilderService,
    PraticaStatusService $praticaStatusService,
    TranslatorInterface $translator,
    LoggerInterface $logger,
    SchemaFactoryInterface $schemaFactory
  )
  {
    $this->moduloPdfBuilderService = $moduloPdfBuilderService;
    $this->praticaStatusService = $praticaStatusService;
    $this->logger = $logger;
    $this->entityManager = $entityManager;
    $this->is = $instanceService;
    $this->translator = $translator;
    $this->schemaFactory = $schemaFactory;
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
  public function assign(Pratica $pratica, User $user)
  {

    if ($pratica->getOperatore() && $pratica->getOperatore()->getFullName() === $user->getFullName()) {
      throw new BadRequestHttpException(
        "La pratica è già assegnata a {$pratica->getOperatore()->getFullName()}"
      );
    }

    if ($pratica->getServizio()->isProtocolRequired() && $pratica->getNumeroProtocollo() === null) {
      throw new BadRequestHttpException("La pratica non ha ancora un numero di protocollo");
    }

    $pratica->setOperatore($user);
    $statusChange = new StatusChange();
    $statusChange->setEvento('Presa in carico');
    $statusChange->setOperatore($user->getFullName());
    $this->praticaStatusService->setNewStatus(
      $pratica,
      Pratica::STATUS_PENDING,
      $statusChange
    );

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
      throw new BadRequestHttpException('La pratica è già stata elaborata');
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
        $this->praticaStatusService->setNewStatus(
          $pratica,
          Pratica::STATUS_PAYMENT_PENDING,
          $statusChange
        );
      }  else {

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
      throw new BadRequestHttpException('La pratica è già stata elaborata');
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

    // todo: verificare se va creato solo il messaggio o anche la richiesta di integrazione, per ora creo entrambi
    $richiestaIntegrazione = new RichiestaIntegrazioneDTO([], null, $data['message']);
    $this->praticaStatusService->validateChangeStatus($pratica, Pratica::STATUS_REQUEST_INTEGRATION);
    $integration = $this->moduloPdfBuilderService->creaModuloProtocollabilePerRichiestaIntegrazione(
      $pratica,
      $richiestaIntegrazione
    );


    $message = new Message();
    $message->setApplication($pratica);
    $message->setProtocolRequired(false);
    $message->setVisibility(Message::VISIBILITY_APPLICANT);
    $message->setMessage($data['message']);
    $message->setSubject($this->translator->trans('pratica.messaggi.oggetto', ['%pratica%' => $message->getApplication()]));
    $message->setAuthor($user);

    foreach ($data['attachments'] as $attachment) {
      $base64Content = $attachment->getFile();
      $file = new UploadedBase64File($base64Content, $attachment->getMimeType());
      $allegato = new AllegatoMessaggio();
      $allegato->setFile($file);
      $allegato->setOwner($pratica->getUser());
      $allegato->setDescription('Allegato richiesta integrazione');
      $allegato->setOriginalFilename($attachment->getName());
      $allegato->setIdRichiestaIntegrazione($integration->getId());
      $this->entityManager->persist($allegato);
      $message->addAttachment($allegato);
    }

    $pratica->addRichiestaIntegrazione($integration);
    $this->entityManager->persist($message);
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
    $integrationsAnswer = $this->moduloPdfBuilderService->creaModuloProtocollabilePerRispostaIntegrazione($pratica, [], true);
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
    $integrationsAnswer = $this->moduloPdfBuilderService->creaModuloProtocollabilePerRispostaIntegrazione($pratica, $messages);
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
    $integrationAnswerRepo = $this->entityManager->getRepository('AppBundle:RispostaIntegrazione');

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
  public function generateStatusMessage(Pratica $pratica, string $text, string $subject, array $callToActions = []): Message
  {
    $message = new Message();
    $message->setApplication($pratica);
    $message->setProtocolRequired(false);
    $message->setVisibility(Message::VISIBILITY_APPLICANT);
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

  public function createDraftApplication(Servizio $servizio, CPSUser $user, array $additionalDematerializedData) {
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
              'surname' => $user->getCognome()
            ]
          ],
          'gender' => [
            'data' => [
              'gender' => $user->getSessoAsString()
            ]
          ],
          'Born' => [
            'data' => [
              'natoAIl' => $user->getDataNascita()->format('d/m/Y'),
              'place_of_birth' => $user->getLuogoNascita()
            ]
          ],
          'fiscal_code' => [
            'data' => [
              'fiscal_code' => $user->getCodiceFiscale(),
            ]
          ],
          'address' => [
            'data' => [
              'address' => $user->getIndirizzoResidenza(),
              'house_number' => '',
              'municipality' => $user->getCittaResidenza(),
              'postal_code' => $user->getCapResidenza(),
              'county' => $user->getProvinciaResidenza(),
            ]
          ],
          'email_address' => $user->getEmail(),
          'email_repeat' => $user->getEmail(),
          'cell_number' => $user->getCellulare(),
          'phone_number' => $user->getTelefono(),
        ]
      ],
      'cell_number' => $user->getCellulare(),
      'phone_number' => $user->getTelefono()
    ];

    $pratica->setDematerializedForms(["data" => array_merge(
      $additionalDematerializedData,
      $cpsUserData
    )]);

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
      //$userRepo = $this->entityManager->getRepository('AppBundle:CPSUser');
      $qb = $this->entityManager->createQueryBuilder()
        ->select('u')
        ->from('AppBundle:CPSUser', 'u')
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
        ->setEmail($data['flattened']['applicant.data.email_address'] ?? $user->getId().'@'.CPSUser::FAKE_EMAIL_DOMAIN)
        ->setEmailContatto(
          $data['flattened']['applicant.data.email_address'] ?? $user->getId().'@'.CPSUser::FAKE_EMAIL_DOMAIN
        )
        ->setNome($data['flattened']['applicant.data.completename.data.name'] ?? '')
        ->setCognome($data['flattened']['applicant.data.completename.data.surname'] ?? '')
        ->setDataNascita($birthDay)
        ->setLuogoNascita(isset($data['flattened']['applicant.data.Born.data.place_of_birth']) && !empty($data['flattened']['applicant.data.Born.data.place_of_birth']) ? $data['flattened']['applicant.data.Born.data.place_of_birth'] : '')
        ->setSdcIndirizzoResidenza(isset($data['flattened']['applicant.data.address.data.address']) && !empty($data['flattened']['applicant.data.address.data.address']) ? $data['flattened']['applicant.data.address.data.address'] : '')
        ->setSdcCittaResidenza(isset($data['flattened']['applicant.data.address.data.municipality']) && !empty($data['flattened']['applicant.data.address.data.municipality']) ? $data['flattened']['applicant.data.address.data.municipality'] : '')
        ->setSdcCapResidenza(isset($data['flattened']['applicant.data.address.data.postal_code']) && !empty($data['flattened']['applicant.data.address.data.postal_code']) ? $data['flattened']['applicant.data.address.data.postal_code'] : '')
        ->setSdcProvinciaResidenza(isset($data['flattened']['applicant.data.address.data.county']) && !empty($data['flattened']['applicant.data.address.data.county']) ? $data['flattened']['applicant.data.address.data.county'] : '');

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
   * @throws Exception
   */
  public function validateUserData(array $data, CPSUser $user)
  {
    if (strcasecmp($data['applicant.data.fiscal_code.data.fiscal_code'], $user->getCodiceFiscale()) != 0) {
      throw new Exception($this->translator->trans('steps.formio.fiscalcode_violation_message'));
    }

    if (strcasecmp($data['applicant.data.completename.data.name'], $user->getNome()) != 0) {
      throw new Exception($this->translator->trans('steps.formio.name_violation_message'));
    }

    if (strcasecmp($data['applicant.data.completename.data.surname'], $user->getCognome()) != 0) {
      throw new Exception($this->translator->trans('steps.formio.surname_violation_message'));
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
    $attachments = [];
    foreach ($flattenedData as $key => $value) {
      // Associa gli allegati alla pratica
      if (isset($this->schema[$key]['type']) && ($this->schema[$key]['type'] == 'file' || $this->schema[$key]['type'] == 'sdcfile')) {
        foreach ($value as $file) {
          $id = $file['data']['id'];
          $attachment = $this->entityManager->getRepository('AppBundle:Allegato')->find($id);
          if ($attachment instanceof Allegato) {
            if (isset($file['fileType']) && !empty($file['fileType'])) {
              $attachment->setDescription($file['fileType']);
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
      $count ++;
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
          $files [$page][$label][]= $attachments[$id];
        }
      }
    }
    return $files;
  }
}
