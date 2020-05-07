<?php

namespace App\Services;

use App\Entity\Allegato;
use App\Entity\AllegatoInterface;
use App\Entity\Integrazione;
use App\Entity\Pratica;
use App\Entity\RichiestaIntegrazione;
use App\Event\ProtocollaAllegatiIntegrazioneSuccessEvent;
use App\Event\ProtocollaAllegatiOperatoreSuccessEvent;
use App\Event\ProtocollaPraticaSuccessEvent;
use App\Event\ProtocollaRichiesteIntegrazioneSuccessEvent;
use App\Protocollo\Exception\AlreadyUploadException;
use App\Protocollo\ProtocolloHandlerInterface;
use App\Protocollo\ProtocolloHandlerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ProtocolloService extends AbstractProtocolloService implements ProtocolloServiceInterface
{
    /**
     * @var ProtocolloHandlerInterface
     */
    protected $handler;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    protected $registry;

    protected $instanceService;

    public function __construct(
        ProtocolloHandlerRegistry $registry,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        EventDispatcherInterface $dispatcher,
        InstanceService $instanceService
    ) {
        $this->registry = $registry;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->dispatcher = $dispatcher;
        $this->instanceService = $instanceService;
    }

    /**
     * @param Pratica $pratica
     * @throws AlreadyUploadException
     * @throws \App\Protocollo\Exception\AlreadySentException
     * @throws \App\Protocollo\Exception\ResponseErrorException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function protocollaPratica(Pratica $pratica)
    {
        $this->validatePratica($pratica);

        $this->getHandler()->sendPraticaToProtocollo($pratica);

        $allegati = $pratica->getAllegati();
        foreach ($allegati as $allegato) {
            try {
                $this->validateUploadFile($pratica, $allegato);
                $this->getHandler()->sendAllegatoToProtocollo($pratica, $allegato);
            } catch (AlreadyUploadException $e) {
            }
        }

        foreach ($pratica->getModuliCompilati() as $allegato) {
            try {
                $this->validateUploadFile($pratica, $allegato);
                $this->getHandler()->sendAllegatoToProtocollo($pratica, $allegato);
            } catch (AlreadyUploadException $e) {
            }
        }

        $this->entityManager->persist($pratica);
        $this->entityManager->flush();

        $this->dispatcher->dispatch(
            new ProtocollaPraticaSuccessEvent($pratica)
        );
    }

    /**
     * @param Pratica $pratica
     * @throws \App\Protocollo\Exception\ParentNotRegisteredException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function protocollaRichiesteIntegrazione(Pratica $pratica)
    {
        $this->validatePraticaForUploadFile($pratica);
        $allegati = $pratica->getRichiesteIntegrazione();

        if (!empty($allegati)) {
            foreach ($allegati as $allegato) {
                try {
                    $this->validateUploadFile($pratica, $allegato);
                    $this->getHandler()->sendRichiestaIntegrazioneToProtocollo($pratica, $allegato);
                } catch (AlreadyUploadException $e) {
                }
            }
            $this->entityManager->persist($pratica);
            $this->entityManager->flush();
        }
        $this->dispatcher->dispatch(
            new ProtocollaRichiesteIntegrazioneSuccessEvent($pratica)
        );
    }

    /**
     * @param Pratica $pratica
     * @throws \App\Protocollo\Exception\ParentNotRegisteredException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function protocollaAllegatiIntegrazione(Pratica $pratica)
    {
        $this->validatePraticaForUploadFile($pratica);
        $allegati = $pratica->getAllegati();

        /** @var Allegato $allegato */
        foreach ($allegati as $allegato) {
            try {
                $this->validateUploadFile($pratica, $allegato);
                if ($allegato->getType() == Integrazione::TYPE_DEFAULT) {
                    $this->getHandler()->sendIntegrazioneToProtocollo($pratica, $allegato);
                } else {
                    $this->getHandler()->sendAllegatoToProtocollo($pratica, $allegato);
                }
            } catch (AlreadyUploadException $e) {
            }
        }

        $this->entityManager->persist($pratica);
        $this->entityManager->flush();

        $richiestaIntegrazione = $pratica->getRichiestaDiIntegrazioneAttiva();
        if ($richiestaIntegrazione instanceof RichiestaIntegrazione) {
            $richiestaIntegrazione->markAsDone();
            $this->entityManager->persist($richiestaIntegrazione);
            $this->entityManager->flush();
        }

        $this->dispatcher->dispatch(
            new ProtocollaAllegatiIntegrazioneSuccessEvent($pratica)
        );
    }

    /**
     * @param Pratica $pratica
     * @throws AlreadyUploadException
     * @throws \App\Protocollo\Exception\AlreadySentException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function protocollaRisposta(Pratica $pratica)
    {
        $this->validateRisposta($pratica);

        $this->getHandler()->sendRispostaToProtocollo($pratica);

        $this->logger->notice('Sending risposta operatore as allegato : id ' . $pratica->getRispostaOperatore()->getId());

        try {
            $this->validateUploadFile($pratica, $pratica->getRispostaOperatore());
            $this->getHandler()->sendAllegatoRispostaToProtocollo($pratica, $pratica->getRispostaOperatore());
        } catch (AlreadyUploadException $e) {
            $this->logger->error($e->getMessage());
        }

        $allegati = $pratica->getAllegatiOperatore();
        foreach ($allegati as $allegato) {
            try {
                $this->validateUploadFile($pratica, $allegato);
                $this->getHandler()->sendAllegatoRispostaToProtocollo($pratica, $allegato);
            } catch (AlreadyUploadException $e) {
                $this->logger->error($e->getMessage());
            }
        }

        $this->entityManager->persist($pratica);
        $this->entityManager->flush();

        $this->dispatcher->dispatch(
            new ProtocollaAllegatiOperatoreSuccessEvent($pratica)
        );
    }

    /**
     * @param Pratica $pratica
     * @param AllegatoInterface $allegato
     * @throws AlreadyUploadException
     * @throws \App\Protocollo\Exception\ParentNotRegisteredException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function protocollaAllegato(Pratica $pratica, AllegatoInterface $allegato)
    {
        $this->validatePraticaForUploadFile($pratica);
        $this->validateUploadFile($pratica, $allegato);

        $this->getHandler()->sendAllegatoToProtocollo($pratica, $allegato);

        $this->entityManager->persist($pratica);
        $this->entityManager->flush();
    }

    /**
     * @return ProtocolloHandlerInterface|mixed|null
     */
    public function getHandler()
    {
        if (null == $this->handler) {
            $this->handler = $this->registry->getHandler($this->instanceService->getTenant()->getProtocolloHandler());
        }
        return $this->handler;
    }
}
