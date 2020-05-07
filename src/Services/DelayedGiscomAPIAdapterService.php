<?php

namespace App\Services;

use App\Entity\GiscomPratica;
use App\Entity\Pratica;
use App\Entity\ScheduledAction;
use App\ScheduledAction\ScheduledActionHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class DelayedGiscomAPIAdapterService implements ScheduledActionHandlerInterface, GiscomAPIAdapterServiceInterface
{
    const SCHEDULED_ITEM_TYPE_SEND = 'giscom.sendPratica';

    const SCHEDULED_ITEM_TYPE_ASK_CFS = 'giscom.askCFs';

    /**
     * @var GiscomAPIAdapterService
     */
    private $giscomAPIAdapterService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ScheduleActionService
     */
    private $scheduleActionService;

    /**
     * @var $em EntityManagerInterface
     */
    private $em;

    public function __construct(
        GiscomAPIAdapterService $giscomAPIAdapterService,
        EntityManagerInterface $em,
        LoggerInterface $logger,
        ScheduleActionService $scheduleActionService
    ) {
        $this->giscomAPIAdapterService = $giscomAPIAdapterService;
        $this->logger = $logger;
        $this->scheduleActionService = $scheduleActionService;
        $this->em = $em;
    }

    /**
     * @param GiscomPratica|Pratica $pratica
     * @throws \App\ScheduledAction\Exception\AlreadyScheduledException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function sendPraticaToGiscom(GiscomPratica $pratica)
    {
        $params = serialize([
            'pratica' => $pratica->getId(),
        ]);

        $this->scheduleActionService->appendAction(
            'ocsdc.giscom_api.adapter',
            self::SCHEDULED_ITEM_TYPE_SEND,
            $params
        );
    }

    /**
     * @param GiscomPratica|Pratica $pratica
     * @throws \App\ScheduledAction\Exception\AlreadyScheduledException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function askRelatedCFsForPraticaToGiscom(GiscomPratica $pratica)
    {
        $params = serialize([
            'pratica' => $pratica->getId(),
        ]);

        $this->scheduleActionService->appendAction(
            'ocsdc.giscom_api.adapter',
            self::SCHEDULED_ITEM_TYPE_ASK_CFS,
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
        if ($action->getType() == self::SCHEDULED_ITEM_TYPE_SEND) {
            $pratica = $this->em->getRepository('App:Pratica')->find($params['pratica']);

            if ($pratica instanceof GiscomPratica) {
                $this->giscomAPIAdapterService->sendPraticaToGiscom($pratica);
            }
        } elseif ($action->getType() == self::SCHEDULED_ITEM_TYPE_ASK_CFS) {
            $pratica = $this->em->getRepository('App:Pratica')->find($params['pratica']);

            if ($pratica instanceof GiscomPratica) {
                $this->giscomAPIAdapterService->askRelatedCFsforPraticaToGiscom($pratica);
            }
        }
    }
}
