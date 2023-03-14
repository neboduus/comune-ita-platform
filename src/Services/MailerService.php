<?php


namespace App\Services;


use App\Entity\AllegatoOperatore;
use App\Entity\CPSUser;
use App\Entity\Ente;
use App\Entity\ModuloCompilato;
use App\Entity\OperatoreUser;
use App\Entity\Pratica;
use App\Entity\Subscriber;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Exception\MessageDisabledException;
use App\Model\FeedbackMessage;
use App\Model\FeedbackMessagesSettings;
use App\Model\Mailer;
use App\Model\SubscriberMessage;
use App\Model\Transition;
use App\Services\FileService\AllegatoFileService;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FileNotFoundException;
use Psr\Log\LoggerInterface;
use Swift_Mailer;
use Doctrine\Persistence\ManagerRegistry;
use Swift_Message;
use Twig\Environment;
use Symfony\Component\Translation\TranslatorInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class MailerService
{
  const SES_CONFIGURATION_SET = 'SesDeliveryLogsToSNS';

  /**
   * @var Swift_Mailer $mailer
   */
  private $mailer;

  /**
   * @var IOService
   */
  private $ioService;

  /**
   * @var TranslatorInterface $translator
   */
  private $translator;

  /**
   * @var Environment
   */
  private $templating;

  /**
   * @var ManagerRegistry
   */
  private $doctrine;

  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * @var PraticaPlaceholderService
   */
  private $praticaPlaceholderService;

  private $blacklistedStates = [
    Pratica::STATUS_REQUEST_INTEGRATION,
    Pratica::STATUS_PROCESSING,
    Pratica::STATUS_SUBMITTED_AFTER_INTEGRATION,
    Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE,
    Pratica::STATUS_CANCELLED_WAITALLEGATIOPERATORE,
  ];

  private $blacklistedNotLegacyStates = [
    Pratica::STATUS_DRAFT_FOR_INTEGRATION,
  ];

  private $blacklistedSDuplicatetates = [
    Pratica::STATUS_PENDING
  ];
  /**
   * @var EntityManagerInterface
   */
  private $entityManager;
  /**
   * @var AllegatoFileService
   */
  private $fileService;

  /**
   * MailerService constructor.
   * @param Swift_Mailer $mailer
   * @param TranslatorInterface $translator
   * @param Environment $templating
   * @param ManagerRegistry $doctrine
   * @param LoggerInterface $logger
   * @param IOService $ioService
   * @param PraticaPlaceholderService $praticaPlaceholderService
   * @param AllegatoFileService $fileService
   */
  public function __construct(
    Swift_Mailer $mailer,
    TranslatorInterface $translator,
    Environment $templating,
    ManagerRegistry $doctrine,
    LoggerInterface $logger,
    IOService $ioService,
    PraticaPlaceholderService $praticaPlaceholderService,
    AllegatoFileService $fileService
  ){
    $this->mailer = $mailer;
    $this->translator = $translator;
    $this->templating = $templating;
    $this->doctrine = $doctrine;
    $this->logger = $logger;
    $this->ioService = $ioService;
    $this->praticaPlaceholderService = $praticaPlaceholderService;
    $this->fileService = $fileService;
  }

  /**
   * @param Pratica $pratica
   * @param $fromAddress
   * @param bool $resend
   * @return int
   */
  public function dispatchMailForPratica(Pratica $pratica, $fromAddress, $resend = false)
  {
    $sentAmount = 0;
    if (in_array($pratica->getStatus(), $this->blacklistedStates)) {
      return $sentAmount;
    }

    // Nel caso di pratiche Formio voglio disabilitare anche l'invio in alcuni stati perchè gestite tramite messaggi
    if (!$pratica->getServizio()->isLegacy() && in_array($pratica->getStatus(), $this->blacklistedNotLegacyStates)) {
      return $sentAmount;
    }

    $sendCPSUserMessage = true;

    if (in_array($pratica->getStatus(), $this->blacklistedSDuplicatetates)) {
      // Check if current status exists in application history more than once
      foreach ($pratica->getHistory() as $item) {
        /** @var Transition $item */
        if ($item->getStatusCode() == $pratica->getStatus() && $item->getDate()->getTimestamp() !== $pratica->getLatestStatusChangeTimestamp()) {
          $sendCPSUserMessage = false;
        }
      }
    }

    $CPSUsermessage = null;
    if ($this->CPSUserHasValidContactEmail($pratica->getUser()) && ($resend || !$this->CPSUserHasAlreadyBeenWarned($pratica)) && $sendCPSUserMessage) {
      try {
        if ($pratica->getServizio()->isIOEnabled()) {
          $CPSUsermessage = $this->setupCPSUserMessage($pratica, $fromAddress, true);
          $sentAmount += $this->ioService->sendMessageForPratica(
            $pratica,
            $CPSUsermessage,
            $this->translator->trans('pratica.email.status_change.subject', ['%id%' => $pratica->getId()]
            )
          );
        }

        $CPSUsermessage = $this->setupCPSUserMessage($pratica, $fromAddress);
        $sentAmount += $this->send($CPSUsermessage);
        $pratica->setLatestCPSCommunicationTimestamp(time());
      } catch (MessageDisabledException $e) {
        $this->logger->info('Notification disabled for current status -  Email: ' . $pratica->getUser()->getEmailContatto() . ' - Pratica: ' . $pratica->getId() . ' ' . $e->getMessage());
      } catch (\Exception $e) {
        $this->logger->error('Error in dispatchMailForPratica - Email: ' . $pratica->getUser()->getEmailContatto() . ' - Pratica: ' . $pratica->getId() . ' ' . $e->getMessage());
      }
    }

    // Invio via pec
    if ($CPSUsermessage instanceof Swift_Message) {
      $this->dispatchPecEmail($pratica, $CPSUsermessage);
    }

    /**
     *Todo: se la pratica è in stato submitted (ancora non ha associato un operatore)
     *  - recuperare indirizzi email degli operatori abilitati alla pratica
     *  - inviare email ad operatori recuperati
     */

    if ($pratica->getStatus() == Pratica::STATUS_SUBMITTED || $pratica->getStatus() == Pratica::STATUS_REGISTERED) {
      // Recupero gli operatori abilitati al servizio
      $qb = $this->doctrine->getManager()->createQueryBuilder()
        ->select('operator')
        ->from('App:OperatoreUser', 'operator')
        ->where('operator.serviziAbilitati LIKE :serviceId')
        ->setParameter('serviceId', '%' . $pratica->getServizio()->getId() . '%');

      $operatorsToNotify = $qb->getQuery()->getResult();

      // Repupero tutti gli operatori appartenenti agli uffici associati al servizio
      foreach ($pratica->getServizio()->getUserGroups() as $userGroup) {
        /** @var UserGroup $userGroup */
        foreach ($userGroup->getUsers() as $user) {
          if (!in_array($user, $operatorsToNotify, true)) {
            $operatorsToNotify[] = $user;
          }
        }
      }

      if (!empty($operatorsToNotify)) {
        // Imposto destinatario principale e cc
        $receiver = array_shift($operatorsToNotify);
        $ccReceivers = $operatorsToNotify;

        try {
          $this->logger->debug('Sending email to operator ' . $receiver->getEmail() . ' - Pratica: ' . $pratica->getId());
          $operatoreUserMessage = $this->setupOperatoreUserMessage($pratica, $fromAddress, $receiver, $ccReceivers);
          $sentAmount += $this->send($operatoreUserMessage);
        } catch (\Exception $e) {
          $this->logger->error('Error in dispatchMailForPratica (All operators): Email: ' . $receiver->getEmail() . ' - Pratica: ' . $pratica->getId() . ' ' . $e->getMessage());
        }
      }
    }

    if ($pratica->getStatus() != Pratica::STATUS_PRE_SUBMIT) {
      /** @var OperatoreUser[] $usersToNotify */
      $operatorsToNotify = [];
      if ($pratica->getOperatore() != null && ($resend || !$this->operatoreUserHasAlreadyBeenWarned($pratica))) {
        // Invio email all'operatore che ha in carico la pratica
        $operatorsToNotify[] = $pratica->getOperatore();
      } elseif ($pratica->getUserGroup()) {
        // Invio email a tutti gli operatori appartenenti al gruppo che ha in carico la pratica
        $operatorsToNotify = $pratica->getUserGroup()->getUsers()->toArray();
      }

      if (!empty($operatorsToNotify)) {
        // Imposto destinatario principale e cc.
        // NB: se la pratica è un carico ad un operatore non vengono notificati gli operatori dell'ufficio
        $receiver = array_shift($operatorsToNotify);
        $ccReceivers = $operatorsToNotify;

        try {
          $operatoreUserMessage = $this->setupOperatoreUserMessage($pratica, $fromAddress, $receiver, $ccReceivers);
          $this->logger->debug('Sending email to operator ' . $receiver->getEmail() . ' - Pratica: ' . $pratica->getId());
          $sentAmount += $this->send($operatoreUserMessage);
          $pratica->setLatestOperatoreCommunicationTimestamp(time());
        } catch (\Exception $e) {
          if ($receiver == $pratica->getOperatore()) {
            $this->logger->error('Error in dispatchMailForPratica (Assigned operator): Email: ' . $pratica->getOperatore()->getEmail() . ' - Pratica: ' . $pratica->getId() . ' ' . $e->getMessage());
          } else {
            $this->logger->error('Error in dispatchMailForPratica (Assigned user group): Email: ' . $receiver->getEmail() . ' - Pratica: ' . $pratica->getId() . ' ' . $e->getMessage());
          }
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
    if (count($failed) > 0) {
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
   * @return string|Swift_Message
   * @throws MessageDisabledException
   * @throws \Twig\Error\Error
   */
  private function setupCPSUserMessage(Pratica $pratica, $fromAddress, $textOnly = false)
  {
    $toEmail = $pratica->getUser()->getEmailContatto();
    $toName = $pratica->getUser()->getFullName();

    $locale = $pratica->getLocale() ?? 'it';

    $ente = $pratica->getEnte();
    $ente->setTranslatableLocale($locale);

    $fromName = $ente instanceof Ente ? $ente->getName() : null;
    $service = $pratica->getServizio();
    $service->setTranslatableLocale($locale);
    $this->doctrine->getManager()->refresh($service);
    $feedbackMessages = $service->getFeedbackMessages();

    if (!isset($feedbackMessages[$pratica->getStatus()])) {
      return $this->setupCPSUserMessageFallback($pratica, $fromAddress, $textOnly, $locale);
    }

    /** @var FeedbackMessage $feedbackMessage */
    $feedbackMessage = $feedbackMessages[$pratica->getStatus()];
    if ((isset($feedbackMessage['isActive']) && !$feedbackMessage['isActive']) || (isset($feedbackMessage['is_active']) && !$feedbackMessage['is_active'])) {
      throw new MessageDisabledException('Message for ' . $pratica->getStatus() . ' is not active');
    }

    $placeholders = $this->praticaPlaceholderService->getPlaceholders($pratica);

    if ($textOnly) {
      return strtr($feedbackMessage['message'], $placeholders);
    }

    if (isset($feedbackMessage['subject']) && !empty($feedbackMessage['subject'])) {
      $subject = strip_tags(strtr($feedbackMessage['subject'], $placeholders));
    } else {
      $subject = $this->translator->trans('pratica.email.status_change.subject', ['%id%' => $pratica->getId()]);
    }

    $textHtml = $this->templating->render(
      'Emails/User/feedback_message.html.twig',
      array(
        'pratica' => $pratica,
        'placeholder' => $placeholders,
        'text' => strtr($feedbackMessage['message'], $placeholders),
        'locale' => $locale,
      )
    );

    $textPlain = strip_tags($textHtml);

    $message = (new Swift_Message())
      ->setSubject($subject)
      ->setFrom($fromAddress, $fromName)
      ->setTo($toEmail, $toName)
      ->setBody($textHtml, 'text/html')
      ->addPart($textPlain, 'text/plain');

    $this->addAttachments($pratica, $message);

    $this->addCustomHeadersToMessage($message);

    return $message;
  }

  /**
   * @param Pratica $pratica
   * @param $fromAddress
   * @param bool $textOnly
   * @param $locale
   * @return Swift_Message
   * @throws FileNotFoundException
   * @throws LoaderError
   * @throws RuntimeError
   * @throws SyntaxError
   */
  private function setupCPSUserMessageFallback(Pratica $pratica, $fromAddress, $textOnly = false, $locale)
  {
    $toEmail = $pratica->getUser()->getEmailContatto();
    $toName = $pratica->getUser()->getFullName();

    $ente = $pratica->getEnte();
    $fromName = $ente instanceof Ente ? $ente->getName() : null;

    $submissionTime = $pratica->getSubmissionTime() ? (new \DateTime())->setTimestamp($pratica->getSubmissionTime()) : null;
    $protocolTime = $pratica->getProtocolTime() ? (new \DateTime())->setTimestamp($pratica->getProtocolTime()) : null;

    $placeholders = array(
      'pratica' => $pratica,
      'user_name' => $pratica->getUser()->getFullName(),
      'data_acquisizione' => $submissionTime ? $submissionTime->format('d/m/Y') : "",
      'ora_acquisizione' => $submissionTime ? $submissionTime->format('H:i:s') : "",
      'data_protocollo' => $protocolTime ? $protocolTime->format('d/m/Y') : "",
      'ora_protocollo' => $protocolTime ? $protocolTime->format('H:i:s') : "",
      'data_corrente' => (new \DateTime())->format('d/m/Y'),
      'locale' => $locale
    );

    if ($textOnly) {
      return $this->templating->render(
        'Emails/User/pratica_status_change.txt.twig',
        $placeholders
      );
    }

    $message = (new Swift_Message())
      ->setSubject($this->translator->trans('pratica.email.status_change.subject', ['%id%' => $pratica->getId()], null, $locale))
      ->setFrom($fromAddress, $fromName)
      ->setTo($toEmail, $toName)
      ->setBody(
        $this->templating->render(
          'Emails/User/pratica_status_change.html.twig',
          $placeholders
        ),
        'text/html'
      )
      ->addPart(
        $this->templating->render(
          'Emails/User/pratica_status_change.txt.twig',
          $placeholders
        ),
        'text/plain'
      );

    $this->addAttachments($pratica, $message);

    $this->addCustomHeadersToMessage($message);

    return $message;
  }


  /**
   * @param Pratica $pratica
   * @param $fromAddress
   * @param OperatoreUser|null $operatore
   * @param OperatoreUser[] $ccOperators
   * @return Swift_Message
   * @throws LoaderError
   * @throws RuntimeError
   * @throws SyntaxError
   */
  private function setupOperatoreUserMessage(Pratica $pratica, $fromAddress, OperatoreUser $operatore = null, array $ccOperators = []): Swift_Message
  {
    if ($operatore == null) {
      $operatore = $pratica->getOperatore();
    }

    $addesses = [];
    foreach ($ccOperators as $ccOperators) {
      $addesses[] = $ccOperators->getEmail();
    }

    $toEmail = $operatore->getEmail();
    $toName = $operatore->getFullName();

    $ente = $pratica->getEnte();
    $fromName = $ente instanceof Ente ? $ente->getName() : null;

    $message = (new Swift_Message())
      ->setSubject($this->translator->trans('pratica.email.status_change.subject', ['%id%' => $pratica->getId()]))
      ->setFrom($fromAddress, $fromName)
      ->setCC($addesses)
      ->setTo($toEmail, $toName)
      ->setBody(
        $this->templating->render(
          'Emails/Operatore/pratica_status_change.html.twig',
          array(
            'pratica' => $pratica,
            'user_name' => $pratica->getOperatore() ? $operatore->getFullName() : $pratica->getUserGroup()->getName(),
          )
        ),
        'text/html'
      )
      ->addPart(
        $this->templating->render(
          'Emails/Operatore/pratica_status_change.txt.twig',
          array(
            'pratica' => $pratica,
            'user_name' => $pratica->getOperatore() ? $operatore->getFullName() : $pratica->getUserGroup()->getName(),
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
        $emailMessage = (new Swift_Message())
          ->setSubject($subject)
          ->setFrom($fromAddress, $fromName)
          ->setTo($toAddress, $toName)
          ->setBody(
            $this->templating->render(
              'Emails/General/message.html.twig',
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
              'Emails/General/message.txt.twig',
              array(
                'message' => $message,
                'ente' => $ente,
              )
            ),
            'text/plain'
          );
        $this->addCustomHeadersToMessage($emailMessage);
        $sentAmount += $this->send($emailMessage);
      } catch (\Exception $e) {
        $this->logger->error('Error in dispatchMail: Email: ' . $toAddress . ' - ' . $e->getMessage());
      }
    } else {
      $this->logger->info('Email: ' . $toAddress . ' is not valid.');
    }

    return $sentAmount;
  }

  /**
   * @param Pratica $pratica
   * @param Swift_Message $message
   */
  public function dispatchPecEmail(Pratica $pratica, Swift_Message $message)
  {
    /** @var FeedbackMessagesSettings $feedbackMessageSettings */
    $feedbackMessageSettings = $pratica->getServizio()->getFeedbackMessagesSettings();
    if ($feedbackMessageSettings != null && $feedbackMessageSettings->getPecMailer() != 'disabled') {
      try {
        /** @var Mailer $instanceMailer */
        $instanceMailer = $pratica->getServizio()->getEnte()->getMailer($feedbackMessageSettings->getPecMailer());

        if (!$instanceMailer instanceof Mailer) {
          throw new \Exception('There are no mailers on instance');
        }

        $transport = (new \Swift_SmtpTransport($instanceMailer->getHost(), $instanceMailer->getPort()))
          ->setUsername($instanceMailer->getUser())
          ->setPassword($instanceMailer->getPassword())
          ->setEncryption($instanceMailer->getEncription());

        // Create the Mailer using your created Transport
        $pecMailer = new Swift_Mailer($transport);

        $submission = PraticaPlaceholderService::getFlattenedSubmission($pratica);
        // Recupero indirizzo email da campo segnalato in pec_receiver
        if (!isset($submission[$feedbackMessageSettings->getPecReceiver()])) {
          $this->logger->error('Error in dispatchPecEmail: emprty pec receiver field');
          return;
        }
        $receiver = $submission[$feedbackMessageSettings->getPecReceiver()];

        if (!$this->isValidEmail($receiver)) {
          $this->logger->error('Error in dispatchPecEmail: pec receiver is not a valid email ' . $receiver);
          return;
        }
        $message->setTo($receiver);
        $message->setFrom($instanceMailer->getSender());
        $failed = [];
        $pecMailer->send($message, $failed);
        if (count($failed) > 0) {
          throw new \Exception(implode(',', $failed));
        }
      } catch (\Exception $e) {
        $this->logger->error('Error in dispatchPecEmail: Email: ' . $pratica->getUser()->getEmailContatto() . ' - Pratica: ' . $pratica->getId() . ' ' . $e->getMessage());
      }
    }
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
  public function dispatchMailForSubscriber(SubscriberMessage $subscriberMessage, $fromAddress, User $operatore)
  {
    $sentAmount = 0;

    if ($this->SubscriberHasValidContactEmail($subscriberMessage->getSubscriber())) {
      try {
        $message = $this->setupSubscriberMessage($subscriberMessage, $fromAddress, $operatore);
        $sentAmount += $this->send($message);
      } catch (\Exception $e) {
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
   * @return Swift_Message
   * @throws \Twig\Error\Error
   */
  private function setupSubscriberMessage(SubscriberMessage $subscriberMessage, $fromAddress, User $operatoreUser)
  {
    $toEmail = $subscriberMessage->getSubscriber()->getEmail();
    $toName = $subscriberMessage->getFullName();

    $ente = $operatoreUser->getEnte();
    $fromName = $ente instanceof Ente ? $ente->getName() : null;

    $emailMessage = (new Swift_Message())
      ->setSubject($subscriberMessage->getSubject())
      ->setFrom($fromAddress, $fromName)
      ->setTo($toEmail, $toName)
      ->setBcc($operatoreUser->getEmail(), $operatoreUser->getFullName())
      ->setBody(
        $this->templating->render(
          'Emails/Subscriber/subscriber_message.html.twig',
          array(
            'message' => $subscriberMessage->getMessage(),
          )
        ),
        'text/html'
      )
      ->addPart(
        $this->templating->render(
          'Emails/Subscriber/subscriber_message.txt.twig',
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
   * @param Swift_Message $message
   */
  private function addCustomHeadersToMessage(Swift_Message $message)
  {
    $message
      ->getHeaders()
      ->addTextHeader('X-SES-CONFIGURATION-SET', self::SES_CONFIGURATION_SET);
  }

  /**
   * @param Pratica $pratica
   * @param Swift_Message $message
   * @throws \League\Flysystem\FileNotFoundException
   */
  private function addAttachments(Pratica $pratica, Swift_Message $message) {
    // Send attachment to user if status is submitted
    if ($pratica->getStatus() == Pratica::STATUS_SUBMITTED) {
      if ($pratica->getModuliCompilati()->count() > 0) {
        /** @var ModuloCompilato $moduloCompilato */
        $moduloCompilato = $pratica->getModuliCompilati()->first();
        if ($this->fileService->attachmentExists($moduloCompilato)) {
          $attachment = new \Swift_Attachment(
            $this->fileService->getAttachmentContent($moduloCompilato),
            $moduloCompilato->getFile()->getFilename(),
            $this->fileService->getAttachmentMimeType($moduloCompilato)
          );
          $message->attach($attachment);
        }
      }
    }

    // Send operator attachment to user if status is complete
    if ($pratica->getStatus() == Pratica::STATUS_COMPLETE) {
      if ($pratica->getAllegatiOperatore()->count() > 0) {
        /** @var AllegatoOperatore $allegato */
        foreach ($pratica->getAllegatiOperatore() as $allegato) {
          if ($this->fileService->attachmentExists($allegato)) {
            $attachment = new \Swift_Attachment(
              $this->fileService->getAttachmentContent($allegato),
              $allegato->getFile()->getFilename(),
              $this->fileService->getAttachmentMimeType($allegato)
            );
            $message->attach($attachment);
          }
        }
      }
    }
  }
}
