<?php


namespace App\Services;


use App\Entity\AllegatoOperatore;
use App\Entity\CPSUser;
use App\Entity\Ente;
use App\Entity\ModuloCompilato;
use App\Entity\OperatoreUser;
use App\Entity\Pratica;
use App\Entity\Subscriber;
use App\Model\FeedbackMessage;
use App\Model\SubscriberMessage;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Form\Extension\Templating\TemplatingExtension;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

class MailerService
{
  const SES_CONFIGURATION_SET = 'SesDeliveryLogsToSNS';

  /**
   * @var \Swift_Mailer $mailer
   */
  private $mailer;

  /**
   * @var TranslatorInterface $translator
   */
  private $translator;

  /**
   * @var TemplatingExtension
   */
  private $templating;

  /**
   * @var RegistryInterface
   */
  private $doctrine;

  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * @var UrlGeneratorInterface
   */
  private $router;

  private $blacklistedStates = [
    Pratica::STATUS_REQUEST_INTEGRATION,
    Pratica::STATUS_PROCESSING,
    Pratica::STATUS_SUBMITTED_AFTER_INTEGRATION,
    Pratica::STATUS_DRAFT,
    Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE,
    Pratica::STATUS_CANCELLED_WAITALLEGATIOPERATORE,
  ];

  /**
   * MailerService constructor.
   * @param \Swift_Mailer $mailer
   * @param TranslatorInterface $translator
   * @param TwigEngine $templating
   * @param RegistryInterface $doctrine
   * @param LoggerInterface $logger
   */
  public function __construct(\Swift_Mailer $mailer, TranslatorInterface $translator, TwigEngine $templating, RegistryInterface $doctrine, LoggerInterface $logger, UrlGeneratorInterface $router)
  {
    $this->mailer = $mailer;
    $this->translator = $translator;
    $this->templating = $templating;
    $this->doctrine = $doctrine;
    $this->logger = $logger;
    $this->router = $router;
  }

  /**
   * @param Pratica $pratica
   * @param $fromAddress
   * @param bool $resend
   * @return int
   * @throws \Twig\Error\Error
   */
  public function dispatchMailForPratica(Pratica $pratica, $fromAddress, $resend = false)
  {
    $sentAmount = 0;
    if (in_array($pratica->getStatus(), $this->blacklistedStates)) {
      return $sentAmount;
    }

    if ($this->CPSUserHasValidContactEmail($pratica->getUser()) && ($resend || !$this->CPSUserHasAlreadyBeenWarned($pratica))) {
      try {
        $CPSUsermessage = $this->setupCPSUserMessage($pratica, $fromAddress);
        $sentAmount += $this->send($CPSUsermessage);
        $pratica->setLatestCPSCommunicationTimestamp(time());
      } catch (\Exception $e){
        $this->logger->error('Error in dispatchMailForPratica: Email: ' . $pratica->getUser()->getEmailContatto() . ' - Pratica: ' . $pratica->getId() . ' ' . $e->getMessage());
      }
    }

    /**
     *Todo: se la pratica è in stato submitted (ancora non ha associato un operatore)
     *  - recuperare indirizzi email degli operatori abilitati alla pratica
     *  - inviare email ad operatori recuperati
     */

    if ($pratica->getStatus() == Pratica::STATUS_SUBMITTED || $pratica->getStatus() == Pratica::STATUS_REGISTERED) {

      $sql = "SELECT id from utente where servizi_abilitati like '%".$pratica->getServizio()->getId()."%'";
      $stmt = $this->doctrine->getManager()->getConnection()->prepare($sql);
      $stmt->execute();
      $result = $stmt->fetchAll();

      $ids = [];
      foreach ($result as $id) {
        $ids[] = $id['id'];
      }

      $repo = $this->doctrine->getRepository('App:OperatoreUser');
      $operatori = $repo->findById($ids);
      if ($operatori != null && !empty($operatori)) {
        foreach ($operatori as $operatore) {
          try {
            $operatoreUserMessage = $this->setupOperatoreUserMessage($pratica, $fromAddress, $operatore);
            $sentAmount += $this->send($operatoreUserMessage);
          } catch (\Exception $e){
            $this->logger->error('Error in dispatchMailForPratica (All operators): Email: ' . $operatore->getEmail() . ' - Pratica: ' . $pratica->getId() . ' ' . $e->getMessage());
          }
        }
      }
    }

    if ($pratica->getStatus() != Pratica::STATUS_PRE_SUBMIT) {
      if ($pratica->getOperatore() != null && ($resend || !$this->operatoreUserHasAlreadyBeenWarned($pratica))) {
        try {
          $operatoreUserMessage = $this->setupOperatoreUserMessage($pratica, $fromAddress);
          $sentAmount += $this->send($operatoreUserMessage);
          $pratica->setLatestOperatoreCommunicationTimestamp(time());
        } catch (\Exception $e){
          $this->logger->error('Error in dispatchMailForPratica (Assigned operator): Email: ' . $pratica->getOperatore()->getEmail() . ' - Pratica: ' . $pratica->getId() . ' ' . $e->getMessage());
        }
      }
    }

    return $sentAmount;
  }

  /**
   * @param $message
   * @return int
   * @throws \Exception
   */
  private function send($message)
  {
    $failed = [];
    $count = $this->mailer->send($message, $failed);
    if (count($failed) > 0){
      throw new \Exception(implode(',', $failed));
    }
    return $count;
  }

  /**
   * @param CPSUser $user
   * @return mixed
   */
  private function CPSUserHasValidContactEmail(CPSUser $user)
  {
    $email = $user->getEmailContatto();

    return filter_var($email, FILTER_VALIDATE_EMAIL);
  }

  /**
   * @param Pratica $pratica
   * @return bool
   */
  private function CPSUserHasAlreadyBeenWarned(Pratica $pratica)
  {
    return $pratica->getLatestCPSCommunicationTimestamp() >= $pratica->getLatestStatusChangeTimestamp();
  }

  /**
   * @param Pratica $pratica
   * @param $fromAddress
   * @return \Swift_Message
   * @throws \Twig\Error\Error
   */
  private function setupCPSUserMessage(Pratica $pratica, $fromAddress)
  {
    $toEmail = $pratica->getUser()->getEmailContatto();
    $toName = $pratica->getUser()->getFullName();

    $ente = $pratica->getEnte();
    $fromName = $ente instanceof Ente ? $ente->getName() : null;

    $feedbackMessages = $pratica->getServizio()->getFeedbackMessages();
    if (!isset($feedbackMessages[$pratica->getStatus()])){
      return $this->setupCPSUserMessageFallback($pratica, $fromAddress);
    }

    /** @var FeedbackMessage $feedbackMessage */
    $feedbackMessage = $feedbackMessages[$pratica->getStatus()];
    if (!$feedbackMessage['isActive']){
      throw new \Exception('Message for '.$pratica->getStatus().' is not active');
    }

    $placeholders = [
      '%pratica_id%' => $pratica->getId(),
      '%servizio%' => $pratica->getServizio()->getName(),
      '%protocollo%' => $pratica->getNumeroProtocollo(),
      '%messaggio_personale%' => !empty(trim($pratica->getMotivazioneEsito())) ? $pratica->getMotivazioneEsito() : $this->translator->trans('messages.pratica.no_reason'),
      '%user_name%' => $pratica->getUser()->getFullName(),
      '%indirizzo%' => $this->router->generate('home', [], UrlGeneratorInterface::ABSOLUTE_URL),
    ];

    $textHtml = $this->templating->render(
      'App:Emails/User:feedback_message.html.twig',
      array(
        'pratica' => $pratica,
        'placeholder' => $placeholders,
        'text' => strtr($feedbackMessage['message'], $placeholders),
      )
    );
    $textPlain = strip_tags($textHtml);

    $message = \Swift_Message::newInstance()
      ->setSubject($this->translator->trans('pratica.email.status_change.subject', ['%id%' => $pratica->getId()]))
      ->setFrom($fromAddress, $fromName)
      ->setTo($toEmail, $toName)
      ->setBody($textHtml, 'text/html')
      ->addPart($textPlain, 'text/plain');

    // Send attachment to user if status is submitted
    if ($pratica->getStatus() == Pratica::STATUS_SUBMITTED) {
      if ($pratica->getModuliCompilati()->count() > 0) {
        /** @var ModuloCompilato $moduloCompilato */
        $moduloCompilato = $pratica->getModuliCompilati()->first();
        if (is_file($moduloCompilato->getFile()->getPathname())) {
          $attachment = \Swift_Attachment::fromPath($moduloCompilato->getFile()->getPathname());
          $attachment->setFilename($moduloCompilato->getFile()->getFilename());
          $message->attach($attachment);
        }
      }
    }

    // Send operator attachment to user if status is complete
    if ($pratica->getStatus() == Pratica::STATUS_COMPLETE) {
      if ($pratica->getAllegatiOperatore()->count() > 0) {
        /** @var AllegatoOperatore $allegato */
        foreach ($pratica->getAllegatiOperatore() as $allegato) {
          if (is_file($allegato->getFile()->getPathname())) {
            $attachment = \Swift_Attachment::fromPath($allegato->getFile()->getPathname());
            $attachment->setFilename($allegato->getFile()->getFilename());
            $message->attach($attachment);
          }
        }
      }
    }

    $this->addCustomHeadersToMessage($message);

    return $message;
  }

  /**
   * @param Pratica $pratica
   * @param $fromAddress
   * @return \Swift_Message
   * @throws \Twig\Error\Error
   */
  private function setupCPSUserMessageFallback(Pratica $pratica, $fromAddress)
  {
    $toEmail = $pratica->getUser()->getEmailContatto();
    $toName = $pratica->getUser()->getFullName();

    $ente = $pratica->getEnte();
    $fromName = $ente instanceof Ente ? $ente->getName() : null;

    $message = \Swift_Message::newInstance()
      ->setSubject($this->translator->trans('pratica.email.status_change.subject', ['%id%' => $pratica->getId()]))
      ->setFrom($fromAddress, $fromName)
      ->setTo($toEmail, $toName)
      ->setBody(
        $this->templating->render(
          'App:Emails/User:pratica_status_change.html.twig',
          array(
            'pratica' => $pratica,
            'user_name'    => $pratica->getUser()->getFullName(),
          )
        ),
        'text/html'
      )
      ->addPart(
        $this->templating->render(
          'App:Emails/User:pratica_status_change.txt.twig',
          array(
            'pratica' => $pratica,
            'user_name'    => $pratica->getUser()->getFullName(),
          )
        ),
        'text/plain'
      );
    // Send attachment to user if status is submitted
    if ($pratica->getStatus() == Pratica::STATUS_SUBMITTED) {
      if ($pratica->getModuliCompilati()->count() > 0 ) {
        $moduloCompilato = $pratica->getModuliCompilati()->first();
        if (is_file($moduloCompilato->getFile()->getPathname())) {
          $message->attach(\Swift_Attachment::fromPath($moduloCompilato->getFile()->getPathname()));
        }
      }
    }

    $this->addCustomHeadersToMessage($message);

    return $message;
  }


  /**
   * @param Pratica $pratica
   * @param $fromAddress
   * @param OperatoreUser|null $operatore
   * @return \Swift_Message
   * @throws \Twig\Error\Error
   */
  private function setupOperatoreUserMessage(Pratica $pratica, $fromAddress, OperatoreUser $operatore = null)
  {
    if ($operatore == null) {
      $operatore = $pratica->getOperatore();
    }

    $toEmail = $operatore->getEmail();
    $toName = $operatore->getFullName();

    $ente = $pratica->getEnte();
    $fromName = $ente instanceof Ente ? $ente->getName() : null;

    $message = \Swift_Message::newInstance()
      ->setSubject($this->translator->trans('pratica.email.status_change.subject', ['%id%' => $pratica->getId()]))
      ->setFrom($fromAddress, $fromName)
      ->setTo($toEmail, $toName)
      ->setBody(
        $this->templating->render(
          'App:Emails/Operatore:pratica_status_change.html.twig',
          array(
            'pratica' => $pratica,
            'user_name' => $operatore->getFullName(),
          )
        ),
        'text/html'
      )
      ->addPart(
        $this->templating->render(
          'App:Emails/Operatore:pratica_status_change.txt.twig',
          array(
            'pratica' => $pratica,
            'user_name' => $operatore->getFullName(),
          )
        ),
        'text/plain'
      );

    $this->addCustomHeadersToMessage($message);

    return $message;
  }

  /**
   * @param Pratica $pratica
   * @return bool
   */
  private function operatoreUserHasAlreadyBeenWarned(Pratica $pratica)
  {
    return $pratica->getLatestOperatoreCommunicationTimestamp() >= $pratica->getLatestStatusChangeTimestamp();
  }

  /**
   * @param $fromAddress
   * @param $fromName
   * @param $toAddress
   * @param $toName
   * @param $message
   * @param $subject
   * @param Ente $ente
   * @param array $callToActions
   * @return int
   */
  public function dispatchMail($fromAddress, $fromName, $toAddress, $toName, $message, $subject, Ente $ente, $callToActions)
  {
    $sentAmount = 0;

    if ($this->isValidEmail($toAddress)) {
      try {
        $emailMessage = \Swift_Message::newInstance()
          ->setSubject($subject)
          ->setFrom($fromAddress, $fromName)
          ->setTo($toAddress, $toName)
          ->setBody(
            $this->templating->render(
              'App:Emails/General:message.html.twig',
              array(
                'message' => $message,
                'ente' => $ente,
                'call_to_actions' => $callToActions
              )
            ),
            'text/html'
          )
          ->addPart(
            $this->templating->render(
              'App:Emails/General:message.txt.twig',
              array(
                'message' => $message,
                'ente' => $ente,
              )
            ),
            'text/plain'
          );
        $this->addCustomHeadersToMessage($emailMessage);
        $sentAmount += $this->send($emailMessage);
      } catch (\Exception $e){
        $this->logger->error('Error in dispatchMail: Email: ' . $toAddress . ' - ' . $e->getMessage());
      }
    } else {
      $this->logger->info('Email: ' . $toAddress . ' is not valid.');
    }

    return $sentAmount;
  }

  /**
   * @param $email
   * @return mixed
   */
  private function isValidEmail($email)
  {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
  }

  /**
   * @param SubscriberMessage $subscriberMessage
   * @param $fromAddress
   * @param OperatoreUser $operatore
   * @return int
   */
  public function dispatchMailForSubscriber(SubscriberMessage $subscriberMessage, $fromAddress, OperatoreUser $operatore)
  {
    $sentAmount = 0;

    if ($this->SubscriberHasValidContactEmail($subscriberMessage->getSubscriber())) {
      try {
        $message = $this->setupSubscriberMessage($subscriberMessage, $fromAddress, $operatore);
        $sentAmount += $this->send($message);
      } catch (\Exception $e){
        $this->logger->error('Error in dispatchMailForSubscriber: Email: ' . $subscriberMessage->getSubscriber()->getEmail() . ' - ' . $e->getMessage());
      }
    }

    return $sentAmount;
  }

  /**
   * @param Subscriber $subscriber
   * @return mixed
   */
  private function SubscriberHasValidContactEmail(Subscriber $subscriber)
  {
    $email = $subscriber->getEmail();

    return filter_var($email, FILTER_VALIDATE_EMAIL);
  }

  /**
   * @param SubscriberMessage $subscriberMessage
   * @param $fromAddress
   * @param OperatoreUser $operatoreUser
   * @return \Swift_Message
   * @throws \Twig\Error\Error
   */
  private function setupSubscriberMessage(SubscriberMessage $subscriberMessage, $fromAddress, OperatoreUser $operatoreUser)
  {
    $toEmail = $subscriberMessage->getSubscriber()->getEmail();
    $toName = $subscriberMessage->getFullName();

    $ente = $operatoreUser->getEnte();
    $fromName = $ente instanceof Ente ? $ente->getName() : null;

    $emailMessage = \Swift_Message::newInstance()
      ->setSubject($subscriberMessage->getSubject())
      ->setFrom($fromAddress, $fromName)
      ->setTo($toEmail, $toName)
      ->setBcc($operatoreUser->getEmail(), $operatoreUser->getFullName())
      ->setBody(
        $this->templating->render(
          'App:Emails/Subscriber:subscriber_message.html.twig',
          array(
            'message' => $subscriberMessage->getMessage(),
          )
        ),
        'text/html'
      )
      ->addPart(
        $this->templating->render(
          'App:Emails/Subscriber:subscriber_message.txt.twig',
          array(
            'message' => $subscriberMessage->getMessage(),
          )
        ),
        'text/plain'
      );
    if ($subscriberMessage->getAutoSend()) {
      $emailMessage->setCc($operatoreUser->getEmail(), $operatoreUser->getFullName());
    }

    $this->addCustomHeadersToMessage($emailMessage);

    return $emailMessage;
  }

  /**
   * @param \Swift_Message $message
   */
  private function addCustomHeadersToMessage(\Swift_Message $message)
  {
    $message
      ->getHeaders()
      ->addTextHeader('X-SES-CONFIGURATION-SET', self::SES_CONFIGURATION_SET);
  }

}
