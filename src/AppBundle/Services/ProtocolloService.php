<?php

namespace AppBundle\Services;

use AppBundle\Entity\AllegatoInterface;
use AppBundle\Entity\Pratica;
use AppBundle\Event\PraticaOnChangeStatusEvent;
use AppBundle\PraticaEvents;
use AppBundle\Protocollo\Exception\AlreadySentException;
use AppBundle\Protocollo\Exception\AlreadyUploadException;
use AppBundle\Protocollo\Exception\ParentNotRegisteredException;
use AppBundle\Protocollo\Exception\InvalidStatusException;
use AppBundle\Protocollo\ProtocolloHandlerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManager;

class ProtocolloService implements ProtocolloServiceInterface
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

        $pratica->setStatus(Pratica::STATUS_REGISTERED);

        foreach ($pratica->getAllegati() as $allegato) {
            $this->handler->sendAllegatoToProtocollo($pratica, $allegato);
        }

        $this->entityManager->persist($pratica);
        $this->entityManager->flush();

        $this->dispatcher->dispatch(
            PraticaEvents::ON_STATUS_CHANGE,
            new PraticaOnChangeStatusEvent($pratica, Pratica::STATUS_REGISTERED)
        );
    }

    public function protocollaAllegato(Pratica $pratica, AllegatoInterface $allegato)
    {
        $this->validatePraticaForUploadFile($pratica, $allegato);
        $this->validateUploadFile($pratica, $allegato);

        $this->handler->sendAllegatoToProtocollo($pratica, $allegato);

        $this->entityManager->persist($pratica);
        $this->entityManager->flush();
    }

    public function getHandler()
    {
        return $this->handler;
    }

    protected function validatePratica(Pratica $pratica)
    {
        if ($pratica->getStatus() == Pratica::STATUS_DRAFT){
            throw new InvalidStatusException();
        }

        if ($pratica->getNumeroFascicolo() !== null) {
            throw new AlreadySentException();
        }

        foreach ($pratica->getAllegati() as $allegato) {
            $this->validateUploadFile($pratica, $allegato);
        }
    }

    protected function validatePraticaForUploadFile(Pratica $pratica, AllegatoInterface $allegato)
    {
        if ($pratica->getNumeroFascicolo() === null) {
            throw new ParentNotRegisteredException();
        }
    }

    protected function validateUploadFile(Pratica $pratica, AllegatoInterface $allegato)
    {
        $alreadySent = false;
        foreach ($pratica->getNumeriProtocollo() as $item) {
            $item = (array)$item;
            if ($item['id'] == $allegato->getId()) {
                $alreadySent = true;
            }
        }

        if ($alreadySent) {
            throw new AlreadyUploadException();
        }
    }
}
