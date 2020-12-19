<?php


namespace App\Services\Manager;


use App\Entity\IntegrabileInterface;
use App\Entity\Message;
use App\Entity\Pratica;
use App\Entity\PraticaRepository;
use App\Entity\RichiestaIntegrazioneDTO;
use App\Entity\StatusChange;
use App\Entity\User;
use App\Logging\LogConstants;
use App\Services\ModuloPdfBuilderService;
use App\Services\PraticaStatusService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

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
   * @var RouterInterface
   */
  private $router;

  /** @var MessageManager */
  private $messageManager;

  /**
   * PraticaManagerService constructor.
   * @param EntityManagerInterface $entityManager
   * @param ModuloPdfBuilderService $moduloPdfBuilderService
   * @param PraticaStatusService $praticaStatusService
   * @param RouterInterface $router
   * @param LoggerInterface $logger
   * @param MessageManager $messageManager
   */
  public function __construct(
    EntityManagerInterface $entityManager,
    ModuloPdfBuilderService $moduloPdfBuilderService,
    PraticaStatusService $praticaStatusService,
    RouterInterface $router,
    LoggerInterface $logger,
    MessageManager $messageManager
  ) {
    $this->moduloPdfBuilderService = $moduloPdfBuilderService;
    $this->praticaStatusService = $praticaStatusService;
    $this->logger = $logger;
    $this->entityManager = $entityManager;
    $this->router = $router;
    $this->messageManager = $messageManager;
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
    $message->setAuthor($user);
    $this->entityManager->persist($message);
    $this->entityManager->persist($pratica);
    $this->entityManager->flush();

    $this->messageManager->dispatchMailForMessage($message, false);


    $statusChange = new StatusChange();
    $statusChange->setOperatore($user->getFullName());
    $this->praticaStatusService->setNewStatus($pratica, Pratica::STATUS_REQUEST_INTEGRATION, $statusChange);
  }

  /**
   * @param Pratica $pratica
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
}
