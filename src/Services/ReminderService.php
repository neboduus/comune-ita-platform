<?php


namespace App\Services;


use App\Entity\Pratica;
use App\Entity\ScheduledAction;
use App\Exception\DelayedScheduledActionException;
use App\ScheduledAction\Exception\AlreadyScheduledException;
use App\ScheduledAction\ScheduledActionHandlerInterface;
use App\Services\Manager\MessageManager;
use App\Services\Manager\PraticaManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;


class ReminderService implements ScheduledActionHandlerInterface
{

  const SCHEDULED_APPLICATION_PAYMENT_REMINDER = 'application_payment_reminder';

  /**
   * @var ScheduleActionService
   */
  private $scheduleActionService;
  /**
   * @var EntityManagerInterface
   */
  private $entityManager;

  /**
   * @var TranslatorInterface
   */
  private $translator;
  /**
   * @var PraticaPlaceholderService
   */
  private $praticaPlaceholderService;
  /**
   * @var PraticaManager
   */
  private $praticaManager;
  /**
   * @var MessageManager
   */
  private $messageManager;
  /**
   * @var RouterInterface
   */
  private $router;

  /**
   * ReminderService constructor.
   * @param ScheduleActionService $scheduleActionService
   * @param EntityManagerInterface $entityManager
   */
  public function __construct(
    ScheduleActionService $scheduleActionService,
    EntityManagerInterface $entityManager,
    TranslatorInterface $translator,
    MessageManager $messageManager,
    PraticaManager $praticaManager,
    RouterInterface $router,
    PraticaPlaceholderService $praticaPlaceholderService
  )
  {
    $this->scheduleActionService = $scheduleActionService;
    $this->entityManager = $entityManager;
    $this->translator = $translator;
    $this->messageManager = $messageManager;
    $this->praticaManager = $praticaManager;
    $this->praticaPlaceholderService = $praticaPlaceholderService;
    $this->router = $router;
  }


  /**
   * @param Pratica $pratica
   * @throws AlreadyScheduledException
   */
  public function createApplicationReminderAsync(Pratica $pratica)
  {
    $params = serialize([
      'pratica' => $pratica->getId(),
      'remindAt' => (new \DateTime())->modify('+30min')->format('c')
    ]);

    $this->scheduleActionService->appendAction(
      'ocsdc.reminder_service',
      self::SCHEDULED_APPLICATION_PAYMENT_REMINDER,
      $params
    );
  }

  /**
   * @param ScheduledAction $action
   * @throws \Exception
   */
  public function executeScheduledAction(ScheduledAction $action)
  {
    $params = unserialize($action->getParams());
    if ($action->getType() == self::SCHEDULED_APPLICATION_PAYMENT_REMINDER) {
      /** @var Pratica $pratica */
      $pratica = $this->entityManager->getRepository('App\Entity\Pratica')->find($params['pratica']);
      if (!$pratica instanceof Pratica) {
        throw new Exception('Not found application with id: ' . $params['pratica']);
      }

      if ($pratica->getStatus() != Pratica::STATUS_PAYMENT_PENDING) {
        $this->scheduleActionService->markAsDone($action);
      } else if  (new \DateTime($params['remindAt']) <= new \DateTime()) {
        $this->sendPaymentReminder($pratica);
      } else {
        throw new DelayedScheduledActionException('Skip reminder for application with id: ' . $params['pratica'] . ' until ' . $params['remindAt']);
      }
    }
  }

  /**
   * @param Pratica $pratica
   */
  public function sendPaymentReminder(Pratica $pratica)
  {
    $callToActions = [];
    $paymentData = $pratica->getPaymentData();

    if ($pratica->getPaymentType() == 'mypay') {
      if (isset($paymentData["response"]["url"])) {
        $callToActions[] = [
          'label'=>'gateway.mypay.redirect_button',
          'link'=>$paymentData["response"]["url"]
        ];
      }
      if (isset($paymentData["response"]["urlFileAvviso"])) {
        $callToActions[] = [
          'label'=>'gateway.mypay.download_button',
          'link'=>htmlspecialchars_decode($paymentData["response"]["urlFileAvviso"])
        ];
      }
    } else {
      $callToActions[] = [
        'label'=>'pratica.vai_alla_pratica',
        'link'=>$this->router->generate(
          'pratica_show_detail',
          ['pratica' => $pratica, 'tab' => 'pagamento'],
          UrlGeneratorInterface::ABSOLUTE_URL
        )
      ];
    }

    $placeholders = $this->praticaPlaceholderService->getPlaceholders($pratica);

    $message = $this->praticaManager->generateStatusMessage(
      $pratica,
      $this->translator->trans('pratica.payment_reminder.message', $placeholders),
      $this->translator->trans('pratica.payment_reminder.subject', $placeholders),
      $callToActions
    );

    $this->messageManager->dispatchMailForMessage($message, false);
  }
}
