<?php


namespace AppBundle\Services\Manager;


use AppBundle\Entity\Pratica;
use AppBundle\Entity\PraticaRepository;
use AppBundle\Entity\StatusChange;
use AppBundle\Entity\User;
use AppBundle\Logging\LogConstants;
use AppBundle\Services\ModuloPdfBuilderService;
use AppBundle\Services\PraticaStatusService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
   * PraticaManagerService constructor.
   * @param EntityManagerInterface $entityManager
   * @param ModuloPdfBuilderService $moduloPdfBuilderService
   * @param PraticaStatusService $praticaStatusService
   * @param LoggerInterface $logger
   */
  public function __construct(EntityManagerInterface $entityManager, ModuloPdfBuilderService $moduloPdfBuilderService, PraticaStatusService $praticaStatusService, LoggerInterface $logger)
  {
    $this->moduloPdfBuilderService = $moduloPdfBuilderService;
    $this->praticaStatusService = $praticaStatusService;
    $this->logger = $logger;
    $this->entityManager = $entityManager;
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
    $statusChange->setMessage('Pratica presa in carico da ' . $user->getFullName());
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
      $statusChange->setMessage('Pratica approvata da ' . $user->getFullName());

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
      $statusChange->setMessage('Pratica rifiutata da ' . $user->getFullName());

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
}
