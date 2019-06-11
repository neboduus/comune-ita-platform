<?php

namespace AppBundle\Services;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\AllegatoInterface;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\RichiestaIntegrazione;
use AppBundle\Event\ProtocollaAllegatiOperatoreSuccessEvent;
use AppBundle\Event\ProtocollaPraticaSuccessEvent;
use AppBundle\Protocollo\Exception\AlreadyUploadException;
use AppBundle\Protocollo\ProtocolloEvents;
use AppBundle\Protocollo\ProtocolloHandlerInterface;
use Doctrine\ORM\EntityManager;
use Google\Spreadsheet\Exception\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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

    public function __construct(
        ProtocolloHandlerInterface $handler,
        EntityManager $entityManager,
        LoggerInterface $logger,
        EventDispatcherInterface $dispatcher
    ) {
        $this->handler = $handler;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->dispatcher = $dispatcher;
    }

    public function protocollaPratica(Pratica $pratica)
    {
        $this->validatePratica($pratica);

        $this->handler->sendPraticaToProtocollo($pratica);

        $allegati = $pratica->getAllegati();
        foreach ($allegati as $allegato) {
            try {
                $this->validateUploadFile($pratica, $allegato);
                $this->handler->sendAllegatoToProtocollo($pratica, $allegato);
            }catch(AlreadyUploadException $e){}
        }

        foreach ($pratica->getModuliCompilati() as $allegato) {
            try {
                $this->validateUploadFile($pratica, $allegato);
                $this->handler->sendAllegatoToProtocollo($pratica, $allegato);
            }catch(AlreadyUploadException $e){}
        }

        $this->entityManager->persist($pratica);
        $this->entityManager->flush();

        $this->dispatcher->dispatch(
            ProtocolloEvents::ON_PROTOCOLLA_PRATICA_SUCCESS,
            new ProtocollaPraticaSuccessEvent($pratica)
        );
    }

    public function protocollaRichiesteIntegrazione(Pratica $pratica)
    {
        $this->validatePraticaForUploadFile($pratica);
        $allegati = $pratica->getRichiesteIntegrazione();

        if (!empty($allegati)) {
            foreach ($allegati as $allegato) {
                try {

                    $this->validateUploadFile($pratica, $allegato);
                    $this->handler->sendAllegatoToProtocollo($pratica, $allegato);

                } catch(AlreadyUploadException $e) {}
            }
            $this->entityManager->persist($pratica);
            $this->entityManager->flush();
        }
        $this->dispatcher->dispatch(
            ProtocolloEvents::ON_PROTOCOLLA_RICHIESTE_INTEGRAZIONE_SUCCESS,
            new ProtocollaPraticaSuccessEvent($pratica)
        );
    }

    public function protocollaAllegatiIntegrazione(Pratica $pratica)
    {
        $this->validatePraticaForUploadFile($pratica);
        $allegati = $pratica->getAllegati();

        /** @var Allegato $allegato */
        foreach ($allegati as $allegato) {
            try {
                $this->validateUploadFile($pratica, $allegato);
                $this->handler->sendAllegatoToProtocollo($pratica, $allegato);
            } catch(AlreadyUploadException $e) {}
        }

        $this->entityManager->persist($pratica);
        $this->entityManager->flush();

        $richiestaIntegrazione = $pratica->getRichiestaDiIntegrazioneAttiva();
        if ($richiestaIntegrazione instanceof RichiestaIntegrazione){
            $richiestaIntegrazione->markAsDone();
            $this->entityManager->persist($richiestaIntegrazione);
            $this->entityManager->flush();
        }

        $this->dispatcher->dispatch(
            ProtocolloEvents::ON_PROTOCOLLA_ALLEGATI_INTEGRAZIONE_SUCCESS,
            new ProtocollaPraticaSuccessEvent($pratica)
        );
    }

    public function protocollaRisposta(Pratica $pratica)
    {
        $this->validateRisposta($pratica);

        $this->handler->sendRispostaToProtocollo($pratica);

        $this->logger->notice('Sending risposta operatore as allegato : id '.$pratica->getRispostaOperatore()->getId());
        try {
            $this->validateUploadFile($pratica, $pratica->getRispostaOperatore());
            $this->handler->sendAllegatoRispostaToProtocollo($pratica, $pratica->getRispostaOperatore());
        } catch(AlreadyUploadException $e) {
            $this->logger->error($e->getMessage());
        }

        $allegati = $pratica->getAllegatiOperatore();
        foreach ($allegati as $allegato) {
            try {
                $this->validateUploadFile($pratica, $allegato);
                $this->handler->sendAllegatoRispostaToProtocollo($pratica, $allegato);
            }catch(AlreadyUploadException $e){
                $this->logger->error($e->getMessage());
            }
        }

        $this->entityManager->persist($pratica);
        $this->entityManager->flush();

        $this->dispatcher->dispatch(
            ProtocolloEvents::ON_PROTOCOLLA_ALLEGATI_OPERATORE_SUCCESS,
            new ProtocollaAllegatiOperatoreSuccessEvent($pratica)
        );
    }

    public function protocollaAllegato(Pratica $pratica, AllegatoInterface $allegato)
    {
        $this->validatePraticaForUploadFile($pratica);
        $this->validateUploadFile($pratica, $allegato);

        $this->handler->sendAllegatoToProtocollo($pratica, $allegato);

        $this->entityManager->persist($pratica);
        $this->entityManager->flush();
    }

    public function getHandler()
    {
        return $this->handler;
    }

}