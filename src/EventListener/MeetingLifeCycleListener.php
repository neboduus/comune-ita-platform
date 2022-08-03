<?php


namespace App\EventListener;


use App\Entity\Meeting;
use App\Services\MeetingService;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use \DateTime;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Exception\ValidatorException;

class MeetingLifeCycleListener
{
  /**
   * @var MeetingService
   */
  private $meetingService;
  /**
   * @var TranslatorInterface
   */
  private $translator;

  public function __construct(MeetingService $meetingService, TranslatorInterface $translator)
  {
    $this->meetingService = $meetingService;
    $this->translator = $translator;
  }

  /**
   * Set createdAt and UpdatedAt and openingHour
   *
   * @param LifecycleEventArgs $args
   * @throws \Exception
   */
  public function prePersist(LifecycleEventArgs $args): void
  {
    $meeting = $args->getObject();
    if ($meeting instanceof Meeting) {
      $errors = $this->meetingService->getMeetingErrors($meeting);
      if (!empty($errors)) {
        throw new ValidatorException($this->translator->trans('meetings.error.invalid_meeting') . ': ' . implode(', ', $errors));
      }

      /*$dateTimeNow = new DateTime();
      $meeting->setUpdatedAt($dateTimeNow);
      $meeting->setCreatedAt($dateTimeNow);*/
      $meeting->setRescheduled(0);
    }
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
    if ($meeting instanceof Meeting && $meeting->getStatus() !== Meeting::STATUS_DRAFT) {
      $this->meetingService->sendEmailNewMeeting($meeting);
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
      $draft = $meeting->getStatus() === Meeting::STATUS_DRAFT || ($args->hasChangedField('status') && $changeSet['status'][0] == Meeting::STATUS_DRAFT);
      $errors = $this->meetingService->getMeetingErrors($meeting);
      if (!empty($errors)) {
        throw new ValidatorException($this->translator->trans('meetings.error.invalid_meeting') . ': ' . implode(', ', $errors));
      }
      //$meeting->setUpdatedAt(new DateTime());


      if (!$draft && ($args->hasChangedField('fromTime') || $args->hasChangedField('toTime'))) {
        $meeting->setRescheduled($meeting->getRescheduled() + 1);
      }

      if ($draft && in_array($meeting->getStatus(), [Meeting::STATUS_PENDING, Meeting::STATUS_APPROVED])) {
        $this->meetingService->sendEmailNewMeeting($meeting);
      } else {
        $this->meetingService->sendEmailUpdatedMeeting($meeting, $args->getEntityChangeSet());
      }
    }
  }

  /**
   * Sends email to citizen when meeting is removed
   *
   * @param LifecycleEventArgs $args
   * @throws \Twig\Error\Error
   */
  public function postRemove(LifecycleEventArgs $args)
  {
    $meeting = $args->getObject();
    if ($meeting instanceof Meeting) {
      $this->meetingService->sendEmailRemovedMeeting($meeting);
    }
  }
}
