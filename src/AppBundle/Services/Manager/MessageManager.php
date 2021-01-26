<?php


namespace AppBundle\Services\Manager;


use AppBundle\Entity\Message;
use AppBundle\Services\InstanceService;
use AppBundle\Services\IOService;
use AppBundle\Services\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

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
   * MessageManager constructor.
   * @param EntityManagerInterface $entityManager
   * @param TranslatorInterface $translator
   * @param InstanceService $instanceService
   * @param RouterInterface $router
   * @param MailerService $mailerService
   * @param IOService $ioService
   * @param FlashBagInterface $flashBag
   * @param string $defaultSender
   */
  public function __construct(
    EntityManagerInterface $entityManager,
    TranslatorInterface $translator,
    InstanceService $instanceService,
    RouterInterface $router,
    MailerService $mailerService,
    IOService $ioService,
    FlashBagInterface $flashBag,
    string $defaultSender
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
  }

  /**
   * @param Message $message
   * @param false $addFlash
   */
  public function dispatchMailForMessage(Message $message, $addFlash = false)
  {
    $sentAmount = 0;

    $subject = $this->translator->trans('pratica.messaggi.oggetto', ['%pratica%' => $message->getApplication()]);
    $mess = $this->translator->trans('pratica.messaggi.messaggio', [
      '%message%' => $message->getMessage(),
      '%link%' => $this->router->generate('track_message', ['id' => $message->getId()], UrlGeneratorInterface::ABSOLUTE_URL) . '?id=' . $message->getId()
    ]);
    $defaultSender = $this->defaultSender;
    $instance = $this->instanceService->getCurrentInstance();
    $userReceiver = $message->getApplication()->getUser();

    if ($message->getApplication()->getServizio()->isIOEnabled()) {
      $sentAmount += $this->ioService->sendMessageForPratica(
        $message->getApplication(),
        $mess,
        $subject
      );
    }
    if ($sentAmount == 0) {
      $this->mailerService->dispatchMail($defaultSender, $instance->getName(), $userReceiver->getEmailContatto(), $userReceiver->getFullName(), $mess, $subject, $instance, $message->getCallToAction());
    }

    $message->setSentAt(time());
    $message->setEmail($userReceiver->getEmailContatto());
    $this->entityManager->persist($message);
    $this->entityManager->flush();
    if ($addFlash) {
      $this->flashBag->add('info', $this->translator->trans('operatori.messaggi.feedback_inviato', ['%email%' =>$message->getApplication()->getUser()->getEmailContatto() ]));
    }
  }
}
