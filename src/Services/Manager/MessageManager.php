<?php


namespace App\Services\Manager;


use App\Entity\CPSUser;
use App\Entity\Message;
use App\Entity\OperatoreUser;
use App\Entity\Pratica;
use App\Event\KafkaEvent;
use App\Event\MessageEvent;
use App\Services\InstanceService;
use App\Services\IOService;
use App\Services\MailerService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MessageManager
{
  /** @var EntityManagerInterface */
  private $entityManager;

  /** @var TranslatorInterface */
  private $translator;

  /** @var InstanceService */
  private $instanceService;

  /** @var RouterInterface */
  private $router;

  /** @var MailerService */
  private $mailerService;

  /** @var FlashBagInterface */
  private  $flashBag;

  private $defaultSender;
  private $ioService;
  /**
   * @var EventDispatcherInterface
   */
  private $dispatcher;


  /**
   * MessageManager constructor.
   * @param EntityManagerInterface $entityManager
   * @param TranslatorInterface $translator
   * @param InstanceService $instanceService
   * @param RouterInterface $router
   * @param MailerService $mailerService
   * @param IOService $ioService
   * @param FlashBagInterface $flashBag
   * @param string $defaultSender
   * @param EventDispatcherInterface $dispatcher
   */
  public function __construct(
    EntityManagerInterface $entityManager,
    TranslatorInterface $translator,
    InstanceService $instanceService,
    RouterInterface $router,
    MailerService $mailerService,
    IOService $ioService,
    FlashBagInterface $flashBag,
    string $defaultSender,
    EventDispatcherInterface $dispatcher
  )
  {
    $this->entityManager = $entityManager;
    $this->translator = $translator;
    $this->instanceService = $instanceService;
    $this->router = $router;
    $this->mailerService = $mailerService;
    $this->ioService = $ioService;
    $this->flashBag = $flashBag;
    $this->defaultSender = $defaultSender;
    $this->dispatcher = $dispatcher;
  }


  /**
   * @param Message $message
   */
  public function save(Message $message)
  {
    $this->entityManager->persist($message);
    $this->entityManager->flush();

    if ($message->getVisibility() == Message::VISIBILITY_APPLICANT) {
      $this->dispatchMailForMessage($message);
    }

    $this->dispatcher->dispatch(new MessageEvent($message), MessageEvent::NAME);
  }

  /**
   * @param Message $message
   * @param false $addFlash
   */
  public function dispatchMailForMessage(Message $message, bool $addFlash = false)
  {

    $subject = $this->translator->trans('pratica.messaggi.oggetto', ['%pratica%' => $message->getApplication()]);
    $mess = $this->translator->trans('pratica.messaggi.messaggio', [
      '%message%' => $message->getMessage(),
      '%link%' => $this->router->generate('track_message', ['id' => $message->getId()], UrlGeneratorInterface::ABSOLUTE_URL) . '?id=' . $message->getId()
    ]);
    $defaultSender = $this->defaultSender;
    $instance = $this->instanceService->getCurrentInstance();

    if ($message->getAuthor() instanceof CPSUser) {
      $fromName = $message->getAuthor()->getFullName();
      $userReceiver = $message->getApplication()->getOperatore();
    } else {
      $fromName = $instance->getName();
      $userReceiver = $message->getApplication()->getUser();
      $addFlash = true;
    }

    $this->mailerService->dispatchMail($defaultSender, $fromName, $userReceiver->getEmailContatto() ?? $userReceiver->getEmail(), $userReceiver->getFullName(), $mess, $subject, $instance, $message->getCallToAction());

    $message->setSentAt(time());
    $message->setEmail($userReceiver->getEmailContatto());
    $this->entityManager->persist($message);
    $this->entityManager->flush();

    // Todo: viene inviato solo nel caso dell'operatore, è veramente  necessario?
    if ($addFlash) {
      if ($message->getApplication()->getServizio()->isIOEnabled()) {
        $this->ioService->sendMessageForPratica(
          $message->getApplication(),
          $mess,
          $subject
        );
      }
      $this->flashBag->add('info', $this->translator->trans('operatori.messaggi.feedback_inviato', ['%email%' =>$message->getApplication()->getUser()->getEmailContatto() ]));
    }
  }
}
