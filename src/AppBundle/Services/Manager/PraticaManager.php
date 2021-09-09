<?php


namespace AppBundle\Services\Manager;


use AppBundle\Entity\CPSUser;
use AppBundle\Entity\FormIO;
use AppBundle\Entity\Message;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\PraticaRepository;
use AppBundle\Entity\RichiestaIntegrazioneDTO;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\StatusChange;
use AppBundle\Entity\User;
use AppBundle\Event\DispatchEmailFromMessageEvent;
use AppBundle\Event\ProtocollaPraticaSuccessEvent;
use AppBundle\Form\FormIO\FormIORenderType;
use AppBundle\Logging\LogConstants;
use AppBundle\Protocollo\ProtocolloEvents;
use AppBundle\Services\InstanceService;
use AppBundle\Services\ModuloPdfBuilderService;
use AppBundle\Services\PraticaStatusService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\ORMException;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class PraticaManager
{
  /**
   * @var
   */
  private $schema = false;

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
   * @var RouterInterface
   */
  private $router;

  /**
   * @var TranslatorInterface
   */
  private $translator;
  /**
   * @var EventDispatcherInterface
   */
  private $dispatcher;

  /**
   * PraticaManagerService constructor.
   * @param EntityManagerInterface $entityManager
   * @param InstanceService $instanceService
   * @param ModuloPdfBuilderService $moduloPdfBuilderService
   * @param PraticaStatusService $praticaStatusService
   * @param TranslatorInterface $translator
   * @param RouterInterface $router
   * @param LoggerInterface $logger
   * @param EventDispatcherInterface $dispatcher
   */
  public function __construct(
    EntityManagerInterface $entityManager,
    InstanceService $instanceService,
    ModuloPdfBuilderService $moduloPdfBuilderService,
    PraticaStatusService $praticaStatusService,
    TranslatorInterface $translator,
    RouterInterface $router,
    LoggerInterface $logger,
    EventDispatcherInterface $dispatcher
  )
  {
    $this->moduloPdfBuilderService = $moduloPdfBuilderService;
    $this->praticaStatusService = $praticaStatusService;
    $this->logger = $logger;
    $this->entityManager = $entityManager;
    $this->is = $instanceService;
    $this->router = $router;
    $this->translator = $translator;
    $this->dispatcher = $dispatcher;
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
    if ($pratica->getOperatore() !== null) {
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
   * @param RichiestaIntegrazioneDTO $integration
   */
  public function requestIntegration(Pratica $pratica, User $user, string $text)
  {
    // todo: verificare se va creato solo il messaggio o anche la richiesta di integrazione, per ora creo entrambi
    $richiestaIntegrazione = new RichiestaIntegrazioneDTO([], null, $text);
    $this->praticaStatusService->validateChangeStatus($pratica, Pratica::STATUS_REQUEST_INTEGRATION);
    $integration = $this->moduloPdfBuilderService->creaModuloProtocollabilePerRichiestaIntegrazione(
      $pratica,
      $richiestaIntegrazione
    );
    $pratica->addRichiestaIntegrazione($integration);

    $message = new Message();
    $message->setApplication($pratica);
    $message->setProtocolRequired(false);
    $message->setVisibility(Message::VISIBILITY_APPLICANT);
    $message->setMessage($text);
    $message->setSubject($this->translator->trans('pratica.messaggi.oggetto', ['%pratica%' => $message->getApplication()]));
    $message->setAuthor($user);
    $this->entityManager->persist($message);
    $this->entityManager->persist($pratica);
    $this->entityManager->flush();

    $this->dispatcher->dispatch(
      DispatchEmailFromMessageEvent::EVENT_IDENTIFIER,
      new DispatchEmailFromMessageEvent($message)
    );

    $statusChange = new StatusChange();
    $statusChange->setOperatore($user->getFullName());
    $statusChange->setMessageId($message->getId());
    $this->praticaStatusService->setNewStatus($pratica, Pratica::STATUS_REQUEST_INTEGRATION, $statusChange);
  }

  /**
   * @param Pratica $pratica
   * @param User $user
   * @throws Exception
   */
  public function acceptIntegration(Pratica $pratica, User $user)
  {
    // Creo il file principale per le integrazioni
    $integrationsAnswer = $this->moduloPdfBuilderService->creaModuloProtocollabilePerRispostaIntegrazione($pratica);
    $pratica->addAllegato($integrationsAnswer);
    $statusChange = new StatusChange();
    $statusChange->setOperatore($user->getFullName());
    $this->praticaStatusService->setNewStatus($pratica, Pratica::STATUS_SUBMITTED_AFTER_INTEGRATION, $statusChange);
  }

  /**
   * @param Pratica $pratica
   * @param string $text
   * @param string $subject
   * @return Message
   */
  public function generateStatusMessage(Pratica $pratica, string $text, string $subject): Message
  {
    $message = new Message();
    $message->setApplication($pratica);
    $message->setProtocolRequired(false);
    $message->setVisibility(Message::VISIBILITY_APPLICANT);
    $message->setMessage($text);
    $message->setSubject($subject);

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
        ($this->schema[$key]['type'] == 'file' || $this->schema[$key]['type'] == 'financial_report')) {
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
}
