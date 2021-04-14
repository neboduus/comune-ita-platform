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
use AppBundle\Form\FormIO\FormIORenderType;
use AppBundle\Logging\LogConstants;
use AppBundle\Services\InstanceService;
use AppBundle\Services\ModuloPdfBuilderService;
use AppBundle\Services\PraticaStatusService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class PraticaManager
{
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

  /** @var MessageManager */
  private $messageManager;

  /**
   * @var TranslatorInterface
   */
  private $translator;

  /**
   * PraticaManagerService constructor.
   * @param EntityManagerInterface $entityManager
   * @param ModuloPdfBuilderService $moduloPdfBuilderService
   * @param PraticaStatusService $praticaStatusService
   * @param TranslatorInterface $translator
   * @param RouterInterface $router
   * @param LoggerInterface $logger
   * @param MessageManager $messageManager
   */
  public function __construct(
    EntityManagerInterface $entityManager,
    InstanceService $instanceService,
    ModuloPdfBuilderService $moduloPdfBuilderService,
    PraticaStatusService $praticaStatusService,
    TranslatorInterface $translator,
    RouterInterface $router,
    LoggerInterface $logger,
    MessageManager $messageManager
  )
  {
    $this->moduloPdfBuilderService = $moduloPdfBuilderService;
    $this->praticaStatusService = $praticaStatusService;
    $this->logger = $logger;
    $this->entityManager = $entityManager;
    $this->is = $instanceService;
    $this->router = $router;
    $this->messageManager = $messageManager;
    $this->translator = $translator;
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

    $this->messageManager->dispatchMailForMessage($message, false);


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

  public function getPlaceholders (Pratica  $pratica) {
    $submissionTime = $pratica->getSubmissionTime() ? (new \DateTime())->setTimestamp($pratica->getSubmissionTime()) : null;
    $protocolTime = $pratica->getProtocolTime() ? (new \DateTime())->setTimestamp($pratica->getProtocolTime()) : null;

    $placeholders = [
      '%id%' => $pratica->getId(),
      '%pratica_id%' => $pratica->getId(),
      '%servizio%' => $pratica->getServizio()->getName(),
      '%protocollo%' => $pratica->getNumeroProtocollo() ? $pratica->getNumeroProtocollo() : "",
      '%messaggio_personale%' => !empty(trim($pratica->getMotivazioneEsito())) ? $pratica->getMotivazioneEsito() : $this->translator->trans('messages.pratica.no_reason'),
      '%user_name%' => $pratica->getUser()->getFullName(),
      '%indirizzo%' => $this->router->generate('home', [], UrlGeneratorInterface::ABSOLUTE_URL),
      '%data_corrente%' => (new \DateTime())->format('d/m/Y'),
      '%data_acquisizione%' => $submissionTime ? $submissionTime->format('d/m/Y') : "",
      '%ora_acquisizione%' => $submissionTime ? $submissionTime->format('H:i:s') : "",
      '%data_protocollo%' => $protocolTime ? $protocolTime->format('d/m/Y') : "",
      '%ora_protocollo%' => $protocolTime ? $protocolTime->format('H:i:s') : ""
    ];

    $dataPlaceholders = [];
    $submission = PraticaManager::getFlattenedSubmission($pratica);
    foreach ($submission as $key => $value) {
      if (!is_array($value)) {
        $dataPlaceholders["%".$key."%"] = (!$value || $value == "") ? "" : $value;
      }
    }

    return array_merge($placeholders, $dataPlaceholders);
  }

  public static function getFlattenedSubmission(Pratica $pratica) {
    $data = is_callable([$pratica, 'getDematerializedForms']) ? $pratica->getDematerializedForms() : [];

    if (!isset($data['flattened'])) {
      return $data;
    }

    $decoratedData = $data['flattened'];
    $submission = array();

    foreach (array_keys($decoratedData) as $path) {
      $parts = explode('.', trim($path, '.'));
      $key = null;
      foreach ($parts as $part) {
        // Salto data
        if ($part === 'data') {
          continue;
        }
        $key = join(".", array_filter(array($key, $part)));
      }
      $submission[$key] = $decoratedData[$path];
    }
    return $submission;
  }

  public function createDraftApplication(Servizio $servizio, CPSUser $user, array $additionalDematerializedData) {
    $pratica = new FormIO();
    $pratica->setUser($user);
    $pratica->setServizio($servizio);
    $pratica->setStatus(Pratica::STATUS_DRAFT);
    $pratica->setEnte($this->is->getCurrentInstance());

    $pratica->setDematerializedForms(["data" => $additionalDematerializedData]);

    $this->entityManager->persist($pratica);
    $this->entityManager->flush();

    return $pratica;
  }
}
