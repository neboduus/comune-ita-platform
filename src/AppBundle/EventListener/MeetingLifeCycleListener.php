<?php


namespace AppBundle\EventListener;


use AppBundle\Entity\Meeting;
use AppBundle\Services\MailerService;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

class MeetingLifeCycleListener
{

  /**
   * @var MailerService
   */
  private $mailer;

  private $defaultSender;

  /**
   * @var TranslatorInterface $translator
   */
  private $translator;
  /**
   * @var UrlGeneratorInterface
   */
  private $router;


  public function __construct(MailerService $mailer, $defaultSender, TranslatorInterface $translator, UrlGeneratorInterface $router)
  {
    $this->mailer = $mailer;
    $this->defaultSender = $defaultSender;
    $this->translator = $translator;
    $this->router = $router;
  }

  /**
   * Sends email to citizen, calendar's moderators and calendar's contact when a new meeting is created
   *
   * @param LifecycleEventArgs $args
   * @throws \Twig\Error\Error
   */
  public function postPersist(LifecycleEventArgs $args)
  {
    $meeting = $args->getObject();
    if ($meeting instanceof Meeting) {
      $status = $meeting->getStatus();
      $calendar = $meeting->getCalendar();
      $ente = $calendar->getOwner()->getEnte();
      $date = $meeting->getFromTime()->format('d/m/Y');
      $hour = $meeting->getFromTime()->format('H:i');
      $contact = $calendar->getContactEmail();

      if ($calendar->getIsModerated() && $status == Meeting::STATUS_PENDING) {
        $userMessage = $this->translator->trans('meetings.email.new_meeting.pending');
      } else if ($status == Meeting::STATUS_APPROVED) {
        $userMessage = $this->translator->trans('meetings.email.new_meeting.approved',
          [
            'hour' => $hour,
            'date' => $date,
            'location' => $calendar->getLocation()
          ]);
        if ($meeting->getvideoconferenceLink()) {
          $userMessage = $userMessage . $this->translator->trans('meetings.email.meeting_link', [
              'videoconference_link' => $meeting->getvideoconferenceLink()
            ]);
        }
      } else return;

      // Add link for cancel meeting
      $userMessage = $userMessage . $this->translator->trans('meetings.email.cancel', [
          'cancel_link' => $this->router->generate('cancel_meeting', [
            'meetingHash' => $meeting->getCancelLink()
          ], UrlGeneratorInterface::ABSOLUTE_URL),
          'email_address' => $contact
        ]);

      // Add mail info
      $userMessage = $userMessage . $this->translator->trans('meetings.email.info', [
          'ente' => $ente->getName(),
          'email_address' => $contact
        ]);

      if ($meeting->getUser()->getEmail()) {
        $this->mailer->dispatchMail(
          $this->defaultSender,
          $ente->getName(),
          $meeting->getUser()->getEmail(),
          $meeting->getUser()->getNome(),
          $userMessage,
          $this->translator->trans('meetings.email.new_meeting.subject'),
          $ente);
      }

      $operatoreMessage = $this->translator->trans('meetings.email.operatori.new_meeting.message', [
        'date' => $date,
        'hour' => $hour,
        'name' => $meeting->getName(),
        'user_message' => $meeting->getUserMessage()
      ]);

      // Send mail to calendar's contact
      if ($calendar->getContactEmail()) {
        $this->mailer->dispatchMail(
          $this->defaultSender,
          $ente->getName(),
          $calendar->getContactEmail(),
          'Contatto Calendario',
          $operatoreMessage,
          $this->translator->trans('meetings.email.operatori.new_meeting.subject'),
          $ente);
      }

      // Send email for each moderator
      if ($calendar->getIsModerated()) {
        foreach ($calendar->getModerators() as $moderator) {
          $this->mailer->dispatchMail(
            $this->defaultSender,
            $ente->getName(),
            $moderator->getEmail(),
            $moderator->getNome(),
            $operatoreMessage . $this->translator->trans('meetings.email.operatori.new_meeting.approve_link', [
              'approve_link' => $this->router->generate(
                'operatori_approve_meeting',
                [
                  'id' => $meeting->getId(),
                ],
                UrlGeneratorInterface::ABSOLUTE_URL)
            ]),
            $this->translator->trans('meetings.email.operatori.new_meeting.subject'),
            $ente);
        }
      }
    }
  }

  /**
   * Sends email when meeting changes
   * @param PreUpdateEventArgs $args
   * @throws \Twig\Error\Error
   */
  public function preUpdate(PreUpdateEventArgs $args)
  {
    $meeting = $args->getObject();
    if ($meeting instanceof Meeting) {
      $changeSet = $args->getEntityChangeSet();
      $statusChanged = key_exists('status', $changeSet);
      $dateChanged = key_exists('fromTime', $changeSet);
      $linkChanged = key_exists('videoconferenceLink', $changeSet);

      if ($dateChanged) {
        $oldDate = $changeSet['fromTime'][0]->format('d/m/Y');
      }
      if ($linkChanged) {
        $oldLink = $changeSet['videoconferenceLink'][0];
      }

      $status = $meeting->getStatus();
      $calendar = $meeting->getCalendar();
      $ente = $calendar->getOwner()->getEnte();
      $date = $meeting->getFromTime()->format('d/m/Y');
      $hour = $meeting->getFromTime()->format('H:i');
      $location = $calendar->getLocation();
      $contact = $calendar->getContactEmail();
      $link = $meeting->getvideoconferenceLink();

      /*
       * invio email se:
       * l'app.to è stato rifiutato (lo stato è cambiato, non mi interessa la data)
       * Lo stato è approvato (non cambiato) ed è stata cambiata la data
       * Lo stato è cambiato in approvato e ho un cambio di data
       * L'app.to è stato approvato
       */


      if ($statusChanged && $status == Meeting::STATUS_REFUSED) {
        // Meeting has been refused. Date change does not matter
        $userMessage = $this->translator->trans('meetings.email.edit_meeting.refused', [
          'date' => $date,
          'email_address' => $contact
        ]);
      } else if ($statusChanged && $status == Meeting::STATUS_CANCELLED) {
        // Meeting has been cancelled. Date change does not matter
        $userMessage = $this->translator->trans('meetings.email.edit_meeting.cancelled', [
          'date' => $date,
          'hour' => $hour
        ]);
      } else if (!$statusChanged && $dateChanged && $status == Meeting::STATUS_APPROVED) {
        // Approved meeting has been rescheduled
        $userMessage = $this->translator->trans('meetings.email.edit_meeting.rescheduled', [
          'old_date' => $oldDate,
          'hour' => $hour,
          'new_date' => $date,
          'location' => $location
        ]);
      } else if ($statusChanged && $dateChanged && $status == Meeting::STATUS_APPROVED) {
        // Auto approved meeting due to date change
        $userMessage = $this->translator->trans('meetings.email.edit_meeting.rescheduled_and_approved', [
          'hour' => $hour,
          'date' => $date,
          'location' => $location
        ]);
      } else if ($statusChanged && !$dateChanged && $status == Meeting::STATUS_APPROVED) {
        // Approved meeting with no date change
        $userMessage = $this->translator->trans('meetings.email.edit_meeting.approved', [
          'hour' => $hour,
          'date' => $date,
          'location' => $location,
        ]);
      } else if (!$statusChanged && !$dateChanged && $linkChanged && $status == Meeting::STATUS_APPROVED) {
        // Videoconference link changed for approved meeting
        if ($link && $oldLink) {
          $userMessage = $this->translator->trans('meetings.email.meeting_link.changed', [
            'videoconference_link' => $link
          ]);
        } else if (!$oldLink) {
          $userMessage = $this->translator->trans('meetings.email.meeting_link.new', [
            'videoconference_link' => $link
          ]);
        } else if (!$link) {
          $userMessage = $this->translator->trans('meetings.email.meeting_link.removed');
        }

      } else return;


      // Add link for cancel meeting if meeting has status approved
      if ($status == Meeting::STATUS_APPROVED) {
        // Append videoconference link
        if ($link && ($statusChanged || $dateChanged)) {
          $userMessage = $userMessage . $this->translator->trans('meetings.email.meeting_link.message', [
              'videoconference_link' => $link
            ]);
        }
        $userMessage = $userMessage . $this->translator->trans('meetings.email.cancel', [
            'cancel_link' => $this->router->generate('cancel_meeting', [
              'meetingHash' => $meeting->getCancelLink()
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'email_address' => $contact
          ]);
      }

      // Add mail info
      $userMessage = $userMessage . $this->translator->trans('meetings.email.info', [
          'ente' => $ente->getName(),
          'email_address' => $contact
        ]);

      if ($meeting->getUser()->getEmail()) {
        $this->mailer->dispatchMail(
          $this->defaultSender,
          $ente->getName(),
          $meeting->getUser()->getEmail(),
          $meeting->getUser()->getNome(),
          $userMessage,
          $this->translator->trans('meetings.email.edit_meeting.subject'),
          $ente);
      }

      if ($statusChanged && $status == Meeting::STATUS_APPROVED) {
        $contactMessage = $this->translator->trans('meetings.email.operatori.meeting_approved.message', [
          'date' => $date,
          'hour' => $hour
        ]);
        $subject = $this->translator->trans('meetings.email.operatori.meeting_approved.subject');
      } else if ($statusChanged && $status == Meeting::STATUS_CANCELLED) {
        $contactMessage = $this->translator->trans('meetings.email.operatori.meeting_cancelled.message', [
          'date' => $date,
          'hour' => $hour
        ]);
        $subject = $this->translator->trans('meetings.email.operatori.meeting_cancelled.subject');
      } else return;

      if ($calendar->getContactEmail()) {
        $this->mailer->dispatchMail(
          $this->defaultSender,
          $ente->getName(),
          $calendar->getContactEmail(),
          'Contatto Calendario',
          $contactMessage,
          $subject,
          $ente);
      }

    }
  }

  /**
   * Sends email to citizen when meeting is removed
   * @param LifecycleEventArgs $args
   * @throws \Twig\Error\Error
   */
  public function postRemove(LifecycleEventArgs $args)
  {
    $meeting = $args->getObject();
    if ($meeting instanceof Meeting) {
      $calendar = $meeting->getCalendar();
      $ente = $calendar->getOwner()->getEnte();

      $message = $this->translator->trans('meetings.email.delete_meeting.delete', [
        'date' => $meeting->getFromTime()->format('d/m/Y'),
        'hour' => $meeting->getFromTime()->format('H:i')
      ]);
      $mailInfo = $this->translator->trans('meetings.email.info', [
        'ente' => $ente->getName(),
        'email_address' => $calendar->getContactEmail()
      ]);
      $message = $message . $mailInfo;


      if ($meeting->getUser()->getEmail()) {
        $this->mailer->dispatchMail(
          $this->defaultSender,
          $ente->getName(),
          $meeting->getUser()->getEmail(),
          $meeting->getUser()->getNome(),
          $message,
          $this->translator->trans('meetings.email.delete_meeting.subject'),
          $ente);
      }
    }
  }
}
