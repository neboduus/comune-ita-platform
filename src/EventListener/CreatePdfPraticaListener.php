<?php

namespace App\EventListener;

use App\BackOffice\BackOfficeInterface;
use App\Entity\DematerializedFormPratica;
use App\Entity\Pratica;
use App\Event\PraticaOnChangeStatusEvent;
use App\ScheduledAction\Exception\AlreadyScheduledException;
use App\Services\ModuloPdfBuilderService;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
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

  public function __construct(ModuloPdfBuilderService $pdfBuilder,  LoggerInterface $logger)
  {
    $this->pdfBuilder = $pdfBuilder;
    $this->logger = $logger;
  }

  public function onStatusChange(PraticaOnChangeStatusEvent $event)
  {
    $pratica = $event->getPratica();

    if ( $event->getNewStateIdentifier() == Pratica::STATUS_PRE_SUBMIT ) {
      try {
        $this->pdfBuilder->createForPraticaAsync($pratica);
      }catch (AlreadyScheduledException $e){
        $this->logger->error('Pdf generation for is already scheduled', ['pratica' => $pratica->getId()]);
      }
    }
  }
}
