<?php

namespace AppBundle\EventListener;

use AppBundle\BackOffice\BackOfficeInterface;
use AppBundle\Entity\DematerializedFormPratica;
use AppBundle\Entity\Pratica;
use AppBundle\Event\PraticaOnChangeStatusEvent;
use AppBundle\ScheduledAction\Exception\AlreadyScheduledException;
use AppBundle\Services\ModuloPdfBuilderService;
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