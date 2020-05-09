<?php

namespace App\EventListener;

use App\Entity\Pratica;
use App\Event\PraticaOnChangeStatusEvent;
use App\Services\ModuloPdfBuilderService;
use Psr\Log\LoggerInterface;

class CreatePdfPraticaListener
{
    /**
     * @var ModuloPdfBuilderService
     */
    protected $pdfBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(ModuloPdfBuilderService $pdfBuilder, LoggerInterface $logger)
    {
        $this->pdfBuilder = $pdfBuilder;
        $this->logger = $logger;
    }

    public function onStatusChange(PraticaOnChangeStatusEvent $event)
    {
        $pratica = $event->getPratica();

        if ($event->getNewStateIdentifier() == Pratica::STATUS_PRE_SUBMIT) {
            $this->pdfBuilder->createForPraticaAsync($pratica);
        }
    }
}
