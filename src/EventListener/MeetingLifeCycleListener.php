<?php

namespace App\EventListener;

use App\Entity\Meeting;
use App\Services\MeetingService;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

class MeetingLifeCycleListener
{
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
