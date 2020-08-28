<?php

namespace AppBundle\Services;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\AllegatoInterface;
use AppBundle\Entity\Integrazione;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\RichiestaIntegrazione;
use AppBundle\Event\ProtocollaAllegatiOperatoreSuccessEvent;
use AppBundle\Event\ProtocollaPraticaSuccessEvent;
use AppBundle\Protocollo\Exception\AlreadySentException;
use AppBundle\Protocollo\Exception\AlreadyUploadException;
use AppBundle\Protocollo\Exception\IncompleteExecutionException;
use AppBundle\Protocollo\Exception\ParentNotRegisteredException;
use AppBundle\Protocollo\ProtocolloEvents;
use AppBundle\Protocollo\ProtocolloHandlerInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use GuzzleHttp\Exception\GuzzleException;
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

  /**
   * Invia al protocollo il documento principale e tutti gli allegati
   * Se tutto è già stato protocollato viene sollevata un eccezione AlreadySentException che permette al cron
   * di rimuovere la schedulazione
   *
   * @param Pratica $pratica
   * @throws AlreadySentException
   * @throws ORMException
   * @throws OptimisticLockException
   */
  public function protocollaPratica(Pratica $pratica)
  {
    $this->validatePratica($pratica);

    if ($pratica->getNumeroProtocollo() === null) {
      $this->logger->debug(__METHOD__.' send pratica', ['pratica' => $pratica->getId()]);
      $this->handler->sendPraticaToProtocollo($pratica);
      $this->entityManager->persist($pratica);
      $this->entityManager->flush();
    }

    $dispatchSuccess = true;

    $allegati = $pratica->getAllegati();
    /** @var Allegato $allegato */
    foreach ($allegati as $allegato) {
      try {
        $this->validateUploadFile($pratica, $allegato);
        $this->logger->debug(
          __METHOD__.': send allegato',
          ['allegato' => $allegato->getId(), 'pratica' => $pratica->getId()]
        );
        $this->handler->sendAllegatoToProtocollo($pratica, $allegato);
        $this->entityManager->persist($pratica);
        $this->entityManager->flush();
      } catch (AlreadyUploadException $e) {
        $this->logger->debug(
          "Allegato già inviato al protocollo",
          ['allegato' => $allegato->getId(), 'pratica' => $pratica->getId()]
        );
      } catch (GuzzleException $e) {
        $this->logger->error(
          "Errore inviando l'allegato al protocollo",
          ['allegato' => $allegato->getId(), 'pratica' => $pratica->getId()]
        );
        $dispatchSuccess = false;
      }
    }

    /** @var Allegato $allegato */
    foreach ($pratica->getModuliCompilati() as $allegato) {
      try {
        $this->validateUploadFile($pratica, $allegato);
        $this->logger->debug(
          __METHOD__.' send moduloCompilato',
          ['allegato' => $allegato->getId(), 'pratica' => $pratica->getId()]
        );
        $this->handler->sendAllegatoToProtocollo($pratica, $allegato);
        $this->entityManager->persist($pratica);
        $this->entityManager->flush();
      } catch (AlreadyUploadException $e) {
        $this->logger->debug(
          "Allegato già inviato al protocollo",
          ['allegato' => $allegato->getId(), 'pratica' => $pratica->getId()]
        );
      } catch (GuzzleException $e) {
        $this->logger->error(
          "Errore inviando l'allegato al protocollo",
          ['allegato' => $allegato->getId(), 'pratica' => $pratica->getId()]
        );
        $dispatchSuccess = false;
      }
    }

    if ($dispatchSuccess) {
      $this->dispatcher->dispatch(
        ProtocolloEvents::ON_PROTOCOLLA_PRATICA_SUCCESS,
        new ProtocollaPraticaSuccessEvent($pratica)
      );
    }else{
      throw new IncompleteExecutionException();
    }
  }

  /**
   * @param Pratica $pratica
   * @throws ParentNotRegisteredException
   * @throws ORMException
   * @throws OptimisticLockException
   */
  public function protocollaRichiesteIntegrazione(Pratica $pratica)
  {
    $this->validatePraticaForUploadFile($pratica);
    $allegati = $pratica->getRichiesteIntegrazione();

    if (!empty($allegati)) {
      foreach ($allegati as $allegato) {
        try {
          $this->validateUploadFile($pratica, $allegato);
          $this->logger->debug(__METHOD__, ['allegato' => $allegato->getId(), 'pratica' => $pratica->getId()]);
          $this->handler->sendRichiestaIntegrazioneToProtocollo($pratica, $allegato);

        } catch (AlreadyUploadException $e) {
        }
      }
      $this->entityManager->persist($pratica);
      $this->entityManager->flush();
    }
    $this->dispatcher->dispatch(
      ProtocolloEvents::ON_PROTOCOLLA_RICHIESTE_INTEGRAZIONE_SUCCESS,
      new ProtocollaPraticaSuccessEvent($pratica)
    );
  }

  /**
   * @param Pratica $pratica
   * @throws ParentNotRegisteredException
   * @throws ORMException
   * @throws OptimisticLockException
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
          $this->logger->debug(
            __METHOD__.' send allegato',
            ['allegato' => $allegato->getId(), 'pratica' => $pratica->getId()]
          );
          $this->handler->sendIntegrazioneToProtocollo($pratica, $allegato);
        } else {
          $this->handler->sendAllegatoToProtocollo($pratica, $allegato);
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
      ProtocolloEvents::ON_PROTOCOLLA_ALLEGATI_INTEGRAZIONE_SUCCESS,
      new ProtocollaPraticaSuccessEvent($pratica)
    );
  }

  /**
   * @param Pratica $pratica
   * @throws AlreadySentException
   * @throws ORMException
   * @throws OptimisticLockException
   */
  public function protocollaRisposta(Pratica $pratica)
  {
    $this->validateRisposta($pratica);
    $this->logger->debug(__METHOD__, ['pratica' => $pratica->getId()]);
    $this->handler->sendRispostaToProtocollo($pratica);
    $this->logger->notice('Sending risposta operatore as allegato : id '.$pratica->getRispostaOperatore()->getId());

    try {
      $this->validateUploadFile($pratica, $pratica->getRispostaOperatore());
      $this->handler->sendAllegatoRispostaToProtocollo($pratica, $pratica->getRispostaOperatore());
    } catch (AlreadyUploadException $e) {
      $this->logger->error($e->getMessage());
    }

    $allegati = $pratica->getAllegatiOperatore();
    foreach ($allegati as $allegato) {
      try {
        $this->validateUploadFile($pratica, $allegato);
        $this->handler->sendAllegatoRispostaToProtocollo($pratica, $allegato);
      } catch (AlreadyUploadException $e) {
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

  /**
   * @param Pratica $pratica
   * @throws AlreadySentException
   * @throws ORMException
   * @throws OptimisticLockException
   */
  public function protocollaRitiro(Pratica $pratica)
  {
    $this->validateRitiro($pratica);
    $this->logger->debug(__METHOD__, ['pratica' => $pratica->getId()]);
    $this->handler->sendRitiroToProtocollo($pratica);

    $this->entityManager->persist($pratica);
    $this->entityManager->flush();
  }

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $allegato
   * @throws AlreadyUploadException
   * @throws ParentNotRegisteredException
   * @throws ORMException
   * @throws OptimisticLockException
   */
  public function protocollaAllegato(Pratica $pratica, AllegatoInterface $allegato)
  {
    $this->validatePraticaForUploadFile($pratica);
    $this->logger->debug(__METHOD__, ['allegato' => $allegato->getId(), 'pratica' => $pratica->getId()]);
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
