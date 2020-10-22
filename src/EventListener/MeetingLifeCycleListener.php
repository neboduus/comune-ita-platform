<?php


namespace App\EventListener;


use App\Entity\Meeting;
use App\Services\InstanceService;
use App\Services\MailerService;
use App\Services\MeetingService;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

class MeetingLifeCycleListener
{
  /**
   * @var MeetingService
   */
  private $meetingService;

  public function __construct(MeetingService $meetingService)
  {
    $this->meetingService = $meetingService;
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
      $this->meetingService->sendEmailUpdatedMeeting($meeting, $args->getEntityChangeSet());
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