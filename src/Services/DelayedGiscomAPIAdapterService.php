<?php

namespace App\Services;

use App\Entity\Pratica;
use App\Entity\GiscomPratica;
use App\Entity\ScheduledAction;
use App\ScheduledAction\ScheduledActionHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Exception;

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
     * @param Pratica|GiscomPratica $pratica
     *
     * @throws \AppBundle\ScheduledAction\Exception\AlreadyScheduledException
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
     * @param Pratica|GiscomPratica $pratica
     *
     * @throws \AppBundle\ScheduledAction\Exception\AlreadyScheduledException
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

    public function executeScheduledAction(ScheduledAction $action)
    {
        $params = unserialize($action->getParams());
        if ($action->getType() == self::SCHEDULED_ITEM_TYPE_SEND) {
            $pratica = $this->em->getRepository('AppBundle:Pratica')->find($params['pratica']);

            if ($pratica instanceof GiscomPratica) {
                $this->giscomAPIAdapterService->sendPraticaToGiscom($pratica);
            }
        } elseif ($action->getType() == self::SCHEDULED_ITEM_TYPE_ASK_CFS) {
            $pratica = $this->em->getRepository('AppBundle:Pratica')->find($params['pratica']);

            if ($pratica instanceof GiscomPratica) {
                $this->giscomAPIAdapterService->askRelatedCFsforPraticaToGiscom($pratica);
            }
        }
    }

}
