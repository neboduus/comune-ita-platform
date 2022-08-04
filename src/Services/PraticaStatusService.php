<?php

namespace App\Services;

use App\Controller\Rest\ApplicationsAPIController;
use App\Entity\Pratica;
use App\Entity\StatusChange;
use App\Event\KafkaEvent;
use App\Event\PraticaOnChangeStatusEvent;
use App\Logging\LogConstants;
use App\PraticaEvents;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PraticaStatusService
{

  const TRANSITIONS_MAPPING = [
    Pratica::STATUS_DRAFT => [
      ApplicationsAPIController::TRANSITION_SUBMIT
    ],
    Pratica::STATUS_SUBMITTED => [
      ApplicationsAPIController::TRANSITION_REGISTER,
      ApplicationsAPIController::TRANSITION_ASSIGN,
      ApplicationsAPIController::TRANSITION_WITHDRAW
    ],
    Pratica::STATUS_REGISTERED => [
      ApplicationsAPIController::TRANSITION_ASSIGN,
      ApplicationsAPIController::TRANSITION_WITHDRAW
    ],
    Pratica::STATUS_PENDING => [
      ApplicationsAPIController::TRANSITION_REQUEST_INTEGRATION,
      ApplicationsAPIController::TRANSITION_ACCEPT,
      ApplicationsAPIController::TRANSITION_REJECT,
      ApplicationsAPIController::TRANSITION_WITHDRAW,
    ],
    Pratica::STATUS_REQUEST_INTEGRATION => [
      ApplicationsAPIController::TRANSITION_REGISTER_INTEGRATION_REQUEST,
    ],
    Pratica::STATUS_DRAFT_FOR_INTEGRATION => [
      ApplicationsAPIController::TRANSITION_ACCEPT_INTEGRATION,
      ApplicationsAPIController::TRANSITION_WITHDRAW,
    ],
    Pratica::STATUS_SUBMITTED_AFTER_INTEGRATION => [
      ApplicationsAPIController::TRANSITION_REGISTER_INTEGRATION_ANSWER
    ],
    Pratica::STATUS_COMPLETE => [
      ApplicationsAPIController::TRANSITION_WITHDRAW
    ],
    Pratica::STATUS_CANCELLED => [
      ApplicationsAPIController::TRANSITION_WITHDRAW
    ],
  ];

  /**
   * @var EntityManager
   */
  private $entityManager;

  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * @var EventDispatcherInterface
   */
  protected $dispatcher;

  private $validChangeStatusList;

  /** @var TranslatorInterface */
  private $translator;

  /**
   * PraticaStatusService constructor.
   *
   * @param EntityManagerInterface $entityManager
   * @param LoggerInterface $logger
   * @param EventDispatcherInterface $dispatcher
   * @param TranslatorInterface $translator
   */
  public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger, EventDispatcherInterface $dispatcher,TranslatorInterface $translator)
  {
    $this->entityManager = $entityManager;
    $this->logger = $logger;
    $this->dispatcher = $dispatcher;
    $this->translator = $translator;

    $this->validChangeStatusList = [

      [Pratica::STATUS_DRAFT => Pratica::STATUS_PRE_SUBMIT],
      [Pratica::STATUS_PRE_SUBMIT => Pratica::STATUS_SUBMITTED],
      [Pratica::STATUS_DRAFT => Pratica::STATUS_PAYMENT_PENDING],

      [Pratica::STATUS_PAYMENT_PENDING => Pratica::STATUS_PAYMENT_OUTCOME_PENDING],
      [Pratica::STATUS_PAYMENT_PENDING => Pratica::STATUS_PAYMENT_SUCCESS],
      [Pratica::STATUS_PAYMENT_PENDING => Pratica::STATUS_PAYMENT_ERROR],
      [Pratica::STATUS_PAYMENT_OUTCOME_PENDING => Pratica::STATUS_PAYMENT_SUCCESS],
      [Pratica::STATUS_PAYMENT_OUTCOME_PENDING => Pratica::STATUS_PAYMENT_ERROR],
      [Pratica::STATUS_PAYMENT_SUCCESS => Pratica::STATUS_PRE_SUBMIT],
      [Pratica::STATUS_PAYMENT_SUCCESS => Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE],
      [Pratica::STATUS_PAYMENT_SUCCESS => Pratica::STATUS_COMPLETE],

      [Pratica::STATUS_SUBMITTED => Pratica::STATUS_REGISTERED],
      [Pratica::STATUS_SUBMITTED => Pratica::STATUS_PENDING],

      [Pratica::STATUS_REGISTERED => Pratica::STATUS_PENDING],
      [Pratica::STATUS_PENDING => Pratica::STATUS_PENDING],
      [Pratica::STATUS_PENDING => Pratica::STATUS_REQUEST_INTEGRATION],

      [Pratica::STATUS_REQUEST_INTEGRATION => Pratica::STATUS_DRAFT_FOR_INTEGRATION],
      [Pratica::STATUS_DRAFT_FOR_INTEGRATION => Pratica::STATUS_SUBMITTED_AFTER_INTEGRATION],

      [Pratica::STATUS_SUBMITTED_AFTER_INTEGRATION => Pratica::STATUS_REGISTERED_AFTER_INTEGRATION],
      [Pratica::STATUS_SUBMITTED_AFTER_INTEGRATION => Pratica::STATUS_PENDING_AFTER_INTEGRATION],
      [Pratica::STATUS_SUBMITTED_AFTER_INTEGRATION => Pratica::STATUS_PENDING],
      [Pratica::STATUS_REGISTERED_AFTER_INTEGRATION => Pratica::STATUS_PENDING_AFTER_INTEGRATION],
      [Pratica::STATUS_REGISTERED_AFTER_INTEGRATION => Pratica::STATUS_PENDING],
      [Pratica::STATUS_PENDING_AFTER_INTEGRATION => Pratica::STATUS_PENDING],
      [Pratica::STATUS_PENDING_AFTER_INTEGRATION => Pratica::STATUS_REQUEST_INTEGRATION],
      [Pratica::STATUS_PENDING_AFTER_INTEGRATION => Pratica::STATUS_COMPLETE],
      [Pratica::STATUS_PENDING_AFTER_INTEGRATION => Pratica::STATUS_CANCELLED],
      [Pratica::STATUS_PENDING_AFTER_INTEGRATION => Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE],
      [Pratica::STATUS_PENDING_AFTER_INTEGRATION => Pratica::STATUS_CANCELLED_WAITALLEGATIOPERATORE],

      [Pratica::STATUS_PENDING => Pratica::STATUS_PAYMENT_PENDING],
      [Pratica::STATUS_PENDING => Pratica::STATUS_COMPLETE],
      [Pratica::STATUS_PENDING => Pratica::STATUS_CANCELLED],
      [Pratica::STATUS_PENDING => Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE],
      [Pratica::STATUS_PENDING => Pratica::STATUS_CANCELLED_WAITALLEGATIOPERATORE],
      [Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE => Pratica::STATUS_COMPLETE],
      [Pratica::STATUS_CANCELLED_WAITALLEGATIOPERATORE => Pratica::STATUS_CANCELLED],
      //[Pratica::STATUS_REGISTERED_AFTER_INTEGRATION => Pratica::STATUS_PENDING],


      //[Pratica::STATUS_REGISTERED => Pratica::STATUS_PROCESSING],
      //[Pratica::STATUS_REGISTERED_AFTER_INTEGRATION => Pratica::STATUS_PROCESSING],
      //[Pratica::STATUS_PROCESSING => Pratica::STATUS_PROCESSING],
      //[Pratica::STATUS_PENDING => Pratica::STATUS_PROCESSING],

      //[Pratica::STATUS_PROCESSING => Pratica::STATUS_COMPLETE],
      //[Pratica::STATUS_PROCESSING => Pratica::STATUS_CANCELLED],
      //[Pratica::STATUS_PROCESSING => Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE],
      //[Pratica::STATUS_PROCESSING => Pratica::STATUS_CANCELLED_WAITALLEGATIOPERATORE],

      // Todo: verificare con giscom se possono essere eliminate
      //[Pratica::STATUS_REGISTERED => Pratica::STATUS_CANCELLED_WAITALLEGATIOPERATORE],
      //[Pratica::STATUS_REGISTERED => Pratica::STATUS_CANCELLED],
      [Pratica::STATUS_CANCELLED => Pratica::STATUS_CANCELLED],

      // Ritiro
      [Pratica::STATUS_PRE_SUBMIT => Pratica::STATUS_WITHDRAW],
      [Pratica::STATUS_SUBMITTED => Pratica::STATUS_WITHDRAW],
      [Pratica::STATUS_REGISTERED => Pratica::STATUS_WITHDRAW],
      [Pratica::STATUS_PENDING => Pratica::STATUS_WITHDRAW],
      [Pratica::STATUS_REQUEST_INTEGRATION => Pratica::STATUS_WITHDRAW],
      [Pratica::STATUS_DRAFT_FOR_INTEGRATION => Pratica::STATUS_WITHDRAW],
      [Pratica::STATUS_REGISTERED_AFTER_INTEGRATION => Pratica::STATUS_WITHDRAW],

      // Riapertura
      [Pratica::STATUS_CANCELLED => Pratica::STATUS_PENDING],
      [Pratica::STATUS_COMPLETE => Pratica::STATUS_PENDING],
      [Pratica::STATUS_WITHDRAW => Pratica::STATUS_PENDING],

    ];
  }

  /**
   * @param Pratica $pratica
   * @param $status
   * @param StatusChange|null $statusChange
   * @param bool $force
   * @throws \Exception
   */
  public function setNewStatus(Pratica $pratica, $status, StatusChange $statusChange = null, $force = false)
  {
    $beforeStatus = $pratica->getStatus();
    $beforeStatusIdentifier = $pratica->getStatusName();

    $states = Pratica::getStatuses();
    if (isset($states[$status]['id'])) {
      $afterStatus = $states[$status]['id'];
      $afterStatusIdentifier = $states[$status]['identifier'];
    } else {
      throw new \Exception($this->translator->trans('errori.pratica.new_status',['%status%' => $pratica->getStatusNameByCode($states)]));
    }

    if ($this->validateChangeStatus($pratica, $afterStatus, $force)) {

      $this->entityManager->beginTransaction();

      try {
        $pratica->setStatus($afterStatus, $statusChange);

        $this->entityManager->persist($pratica);
        $this->entityManager->flush();
        $this->entityManager->refresh($pratica);
        $this->entityManager->commit();

        $this->dispatcher->dispatch(new KafkaEvent($pratica));
        $this->dispatcher->dispatch(new PraticaOnChangeStatusEvent($pratica, $afterStatus, $beforeStatus));


        $this->logger->info(
          LogConstants::PRATICA_CHANGED_STATUS,
          [
            'pratica' => $pratica->getId(),
            'before_status' => $beforeStatusIdentifier,
            'after_status' => $afterStatusIdentifier,
          ]
        );

      } catch (\Exception $e) {
        $this->entityManager->rollback();
        $this->logger->error(
          LogConstants::PRATICA_CHANGED_STATUS_FAILED,
          [
            'pratica' => $pratica->getId(),
            'before_status' => $beforeStatusIdentifier,
            'after_status' => $afterStatusIdentifier,
            'error' => $e->getMessage(),
          ]
        );
      }

    } else {
      throw new \Exception($this->translator->trans('errori.pratica.change_status_invalid'));
    }
  }

  /**
   * @return array
   */
  public function getValidChangeStatusList()
  {
    return $this->validChangeStatusList;
  }

  public function validateChangeStatus(Pratica $pratica, $afterStatus, $force = false)
  {
    $beforeStatus = $pratica->getStatus();
    $beforeStatusName =  $this->translator->trans($pratica->getStatusName());
    $afterStatusName =  $this->translator->trans($pratica->getStatusNameByCode($afterStatus));

    if ($beforeStatus == $afterStatus || $force) {
      return true;
    }

    foreach ($this->validChangeStatusList as $change) {
      foreach ($change as $before => $after) {
        if ($before == $beforeStatus && $after == $afterStatus) {
          return true;
        }
      }
    }
    throw new \Exception($this->translator->trans('errori.pratica.change_status',
      ['%before_status%' => $beforeStatusName, '%after_status%' => $afterStatusName, '%service_name%' => $pratica->getServizio()->getName(), '%id%' => $pratica->getId()]));
  }

  /**
   * @param Pratica $application
   * @return array
   */
  public function getValidChangeStatusListByApplication(Pratica $application)
  {
    $availableTransitions = [];
    foreach ($this->getValidChangeStatusList() as $v) {
      if (array_keys($v)[0] == $application->getStatus()) {
        $availableTransitions[array_values($v)[0]] = array(
          'status_code' => array_values($v)[0],
          'status_name' => $application->getStatusNameByCode(array_values($v)[0])
        );
      }
    }
    return $availableTransitions;
  }

}
