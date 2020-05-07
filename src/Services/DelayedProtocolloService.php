<?php

namespace App\Services;

use App\Entity\AllegatoInterface;
use App\Entity\Pratica;
use App\Entity\ScheduledAction;
use App\Protocollo\Exception\AlreadySentException;
use App\ScheduledAction\ScheduledActionHandlerInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class DelayedProtocolloService extends AbstractProtocolloService implements ProtocolloServiceInterface, ScheduledActionHandlerInterface
{
    const SCHEDULED_ITEM_PROTOCOLLA_PRATICA = 'protocollo.sendPratica';

    const SCHEDULED_ITEM_PROTOCOLLA_ALLEGATI = 'protocollo.sendAllegati';

    const SCHEDULED_ITEM_PROTOCOLLA_RICHIESTE_INTEGRAZIONE = 'protocollo.sendRichiesteIntegrazione';

    const SCHEDULED_ITEM_PROTOCOLLA_RISPOSTA = 'protocollo.refreshPratica';

    const SCHEDULED_ITEM_PROTOCOLLA_ALLEGATO = 'protocollo.uploadFile';

    /**
     * @var ProtocolloService
     */
    protected $protocolloService;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var ScheduleActionService
     */
    protected $scheduleActionService;


    public function __construct(
        ProtocolloService $protocolloService,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        ScheduleActionService $scheduleActionService
    ) {
        $this->protocolloService = $protocolloService;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->scheduleActionService = $scheduleActionService;
    }

    /**
     * @param Pratica $pratica
     * @throws AlreadySentException
     * @throws \App\Protocollo\Exception\AlreadyUploadException
     * @throws \App\ScheduledAction\Exception\AlreadyScheduledException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function protocollaPratica(Pratica $pratica)
    {
        $this->validatePratica($pratica);

        $params = serialize([
            'pratica' => $pratica->getId(),
        ]);

        $this->scheduleActionService->appendAction(
            'ocsdc.protocollo',
            self::SCHEDULED_ITEM_PROTOCOLLA_PRATICA,
            $params
        );
    }

    /**
     * @param Pratica $pratica
     * @throws \App\Protocollo\Exception\ParentNotRegisteredException
     * @throws \App\ScheduledAction\Exception\AlreadyScheduledException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function protocollaRichiesteIntegrazione(Pratica $pratica)
    {
        $this->validatePraticaForUploadFile($pratica);

        $params = serialize([
            'pratica' => $pratica->getId(),
        ]);

        $this->scheduleActionService->appendAction(
            'ocsdc.protocollo',
            self::SCHEDULED_ITEM_PROTOCOLLA_RICHIESTE_INTEGRAZIONE,
            $params
        );
    }

    /**
     * @param Pratica $pratica
     * @throws \App\Protocollo\Exception\ParentNotRegisteredException
     * @throws \App\ScheduledAction\Exception\AlreadyScheduledException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function protocollaAllegatiIntegrazione(Pratica $pratica)
    {
        $this->validatePraticaForUploadFile($pratica);

        $params = serialize([
            'pratica' => $pratica->getId(),
        ]);

        $this->scheduleActionService->appendAction(
            'ocsdc.protocollo',
            self::SCHEDULED_ITEM_PROTOCOLLA_ALLEGATI,
            $params
        );
    }

    /**
     * @param Pratica $pratica
     * @throws \App\Protocollo\Exception\ParentNotRegisteredException
     * @throws \App\ScheduledAction\Exception\AlreadyScheduledException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function protocollaRisposta(Pratica $pratica)
    {
        $this->validatePraticaForUploadFile($pratica);
        $params = serialize([
            'pratica' => $pratica->getId(),
        ]);

        $this->scheduleActionService->appendAction(
            'ocsdc.protocollo',
            self::SCHEDULED_ITEM_PROTOCOLLA_RISPOSTA,
            $params
        );
    }

    /**
     * @param Pratica $pratica
     * @param AllegatoInterface $allegato
     * @throws \App\Protocollo\Exception\AlreadyUploadException
     * @throws \App\ScheduledAction\Exception\AlreadyScheduledException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function protocollaAllegato(Pratica $pratica, AllegatoInterface $allegato)
    {
        $this->validateUploadFile($pratica, $allegato);

        $params = serialize([
            'pratica' => $pratica->getId(),
            'allegato' => $allegato->getId()
        ]);

        $this->scheduleActionService->appendAction(
            'ocsdc.protocollo',
            self::SCHEDULED_ITEM_PROTOCOLLA_ALLEGATO,
            $params
        );
    }

    /**
     * @return \App\Protocollo\ProtocolloHandlerInterface|mixed|null
     */
    public function getHandler()
    {
        return $this->protocolloService->getHandler();
    }

    /**
     * @param ScheduledAction $action
     * @throws \App\Protocollo\Exception\AlreadyUploadException
     * @throws \App\Protocollo\Exception\ParentNotRegisteredException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function executeScheduledAction(ScheduledAction $action)
    {
        $params = unserialize($action->getParams());
        try {
            if ($action->getType() == self::SCHEDULED_ITEM_PROTOCOLLA_PRATICA) {
                $pratica = $this->entityManager->getRepository('App:Pratica')->find($params['pratica']);

                if ($pratica instanceof Pratica) {
                    $this->protocolloService->protocollaPratica($pratica);
                }
            } elseif ($action->getType() == self::SCHEDULED_ITEM_PROTOCOLLA_RICHIESTE_INTEGRAZIONE) {
                $pratica = $this->entityManager->getRepository('App:Pratica')->find($params['pratica']);

                if ($pratica instanceof Pratica) {
                    $this->protocolloService->protocollaRichiesteIntegrazione($pratica);
                }
            } elseif ($action->getType() == self::SCHEDULED_ITEM_PROTOCOLLA_ALLEGATI) {
                $pratica = $this->entityManager->getRepository('App:Pratica')->find($params['pratica']);

                if ($pratica instanceof Pratica) {
                    $this->protocolloService->protocollaAllegatiIntegrazione($pratica);
                }
            } elseif ($action->getType() == self::SCHEDULED_ITEM_PROTOCOLLA_RISPOSTA) {
                $pratica = $this->entityManager->getRepository('App:Pratica')->find($params['pratica']);

                if ($pratica instanceof Pratica) {
                    $this->protocolloService->protocollaRisposta($pratica);
                }
            } elseif ($action->getType() == self::SCHEDULED_ITEM_PROTOCOLLA_ALLEGATO) {
                $allegato = $this->entityManager->getRepository('App:Allegato')->find($params['allegato']);
                $pratica = $this->entityManager->getRepository('App:Pratica')->find($params['pratica']);

                if ($pratica instanceof Pratica && $allegato instanceof AllegatoInterface) {
                    $this->protocolloService->protocollaAllegato($pratica, $allegato);
                }
            }
        } catch (AlreadySentException $e) {
            $this->logger->warning($e->getMessage());
        }
    }
}
