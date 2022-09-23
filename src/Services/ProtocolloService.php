<?php

namespace App\Services;

use App\Entity\Allegato;
use App\Entity\AllegatoInterface;
use App\Entity\AllegatoMessaggio;
use App\Entity\Integrazione;
use App\Entity\IntegrazioneRepository;
use App\Entity\Pratica;
use App\Entity\RichiestaIntegrazione;
use App\Entity\RispostaIntegrazione;
use App\Entity\RispostaIntegrazioneRepository;
use App\Event\ProtocollaAllegatiOperatoreSuccessEvent;
use App\Event\ProtocollaPraticaSuccessEvent;
use App\Protocollo\Exception\AlreadySentException;
use App\Protocollo\Exception\AlreadyUploadException;
use App\Protocollo\Exception\IncompleteExecutionException;
use App\Protocollo\Exception\ParentNotRegisteredException;
use App\Protocollo\PredisposedProtocolHandlerInterface;
use App\Protocollo\ProtocolloHandlerInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
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
    EntityManagerInterface $entityManager,
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

    // Predispose document
    if ($this->handler instanceof PredisposedProtocolHandlerInterface && $pratica->getNumeroProtocollo() === null) {
      try {
        $this->handler->protocolPredisposed($pratica);
        $this->entityManager->persist($pratica);
        $this->entityManager->flush();
      } catch (\Exception $e) {
        $this->logger->error(
          "Errore nella predisposizione della pratica", ['pratica' => $pratica->getId()]
        );
        $dispatchSuccess = false;
      }
    }

    if ($dispatchSuccess) {
      // Nb: I subscriber sono registrati sulla classe quindi non va passato il secondo parametro Name
      $this->dispatcher->dispatch(new ProtocollaPraticaSuccessEvent($pratica));
    } else {
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
      /** @var Allegato $allegato */
      foreach ($allegati as $allegato) {
        if ($allegato->getType() === RichiestaIntegrazione::TYPE_DEFAULT) {
          try {
            $this->validateRichiestaIntegrazione($pratica, $allegato);
            $this->logger->debug(__METHOD__, ['allegato' => $allegato->getId(), 'pratica' => $pratica->getId()]);
            $this->handler->sendRichiestaIntegrazioneToProtocollo($pratica, $allegato);
            $this->entityManager->persist($allegato);
            $this->entityManager->flush();
          } catch (AlreadySentException $e) {
          }
        }
      }
      $this->entityManager->persist($pratica);
      $this->entityManager->flush();
    }
    // Nb: I subscriber sono registrati sulla classe quindi non va passato il secondo parametro Name
    $this->dispatcher->dispatch(new ProtocollaPraticaSuccessEvent($pratica));
  }

  /**
   * @param Pratica $pratica
   * @throws ParentNotRegisteredException
   * @throws ORMException
   * @throws OptimisticLockException
   * @throws IncompleteExecutionException
   */
  public function protocollaAllegatiIntegrazione(Pratica $pratica)
  {
    $this->validatePraticaForUploadFile($pratica);
    $integrationRequest = $pratica->getRichiestaDiIntegrazioneAttiva();

    if (!$integrationRequest instanceof RichiestaIntegrazione) {
      $this->logger->error("Non ci sono richieste di integrazioni attive", ['pratica' => $pratica->getId()]);
      return false;
    }

    $dispatchSuccess = true;

    /** @var RispostaIntegrazioneRepository $integrationAnswerRepo */
    $integrationAnswerRepo = $this->entityManager->getRepository('App\Entity\RispostaIntegrazione');

    /** @var RispostaIntegrazione $integrationAnswer */
    $integrationAnswerCollection = $integrationAnswerRepo->findByIntegrationRequest($integrationRequest->getId());
    if (empty($integrationAnswerCollection)) {
      throw new \Exception('Integration answer not found');
    }
    $integrationAnswer = $integrationAnswerCollection[0];

    try {
      $this->validateRispostaIntegrazione($integrationAnswer);
      $this->logger->debug(__METHOD__.' send risposta', ['risposta_integrazione' => $pratica->getId()]);
      $this->handler->sendRispostaIntegrazioneToProtocollo($pratica, $integrationAnswer);
      $this->entityManager->persist($integrationAnswer);
      $this->entityManager->flush();
      $dispatchSuccess = true;

    } catch (AlreadySentException $e) {
      $this->logger->debug(
        "Risposta la risposta integrazione già inviata al protocollo",
        ['risposta_integrazione' => $integrationAnswer->getId(), 'pratica' => $pratica->getId()]
      );
    } catch (GuzzleException $e) {
      $this->logger->error(
        "Errore inviando la risposta integrazion al protocollo",
        ['risposta_integrazione' => $integrationAnswer->getId(), 'pratica' => $pratica->getId()]
      );
      $dispatchSuccess = false;
    }

    /** @var IntegrazioneRepository $integrationRepo */
    $integrationRepo = $this->entityManager->getRepository('App\Entity\Integrazione');

    /** @var Integrazione[] $integrations */
    $integrations = $integrationRepo->findByIntegrationRequest($integrationRequest->getId());

    foreach ($integrations as $allegato) {
      try {
        $this->validateUploadFile($pratica, $allegato);
        $this->logger->debug(
          __METHOD__.': send integrazione',
          ['integrazione' => $allegato->getId(), 'pratica' => $pratica->getId()]
        );
        $this->handler->sendIntegrazioneToProtocollo($pratica, $integrationAnswer, $allegato);
        $this->entityManager->persist($allegato);
        $this->entityManager->persist($pratica);
        $this->entityManager->flush();

      } catch (AlreadyUploadException $e) {
        $this->logger->debug(
          "Integrazione già inviata al protocollo",
          ['integrazione' => $allegato->getId(), 'pratica' => $pratica->getId()]
        );
      } catch (GuzzleException $e) {
        $this->logger->error(
          "Errore inviando l'allegato al protocollo",
          ['integrazione' => $allegato->getId(), 'pratica' => $pratica->getId()]
        );
        $dispatchSuccess = false;
      }
    }

    if (!empty($integrationAnswer->getAttachments())) {

      $allegatoMessaggioRepo = $this->entityManager->getRepository('App\Entity\AllegatoMessaggio');

      foreach ($integrationAnswer->getAttachments() as $allegatoId) {
        $allegato = $allegatoMessaggioRepo->find($allegatoId);
        if ($allegato instanceof AllegatoMessaggio) {
          try {
            $this->validateUploadFile($pratica, $allegato);
            $this->logger->debug(
              __METHOD__.': send allegato_messaggio',
              ['allegato_messaggio' => $allegato->getId(), 'pratica' => $pratica->getId()]
            );
            $this->handler->sendIntegrazioneToProtocollo($pratica, $integrationAnswer, $allegato);
            $this->entityManager->persist($allegato);
            $this->entityManager->persist($pratica);
            $this->entityManager->flush();

          } catch (AlreadyUploadException $e) {
            $this->logger->debug(
              "Allegato messaggio già inviata al protocollo",
              ['allegato_messaggio' => $allegato->getId(), 'pratica' => $pratica->getId()]
            );
          } catch (GuzzleException $e) {
            $this->logger->error(
              "Errore inviando l'allegato al protocollo",
              ['allegato_messaggio' => $allegato->getId(), 'pratica' => $pratica->getId()]
            );
            $dispatchSuccess = false;
          }
        }
      }
    }

    // Predispose document
    if ($this->handler instanceof PredisposedProtocolHandlerInterface && $integrationAnswer->getNumeroProtocollo() === null) {
      try {
        $this->handler->protocolPredisposedAttachment($pratica, $integrationAnswer);
        $this->entityManager->persist($integrationAnswer);
        $this->entityManager->persist($pratica);
        $this->entityManager->flush();
      } catch (\Exception $e) {
        $this->logger->error("Errore nella predisposizione della pratica", ['pratica' => $pratica->getId()]);
        $dispatchSuccess = false;
      }
    }

    if ($dispatchSuccess) {
      $integrationRequest->markAsDone();
      $this->entityManager->persist($integrationRequest);

      $integrationAnswer->markAsDone();
      $this->entityManager->persist($integrationAnswer);

      $this->entityManager->flush();

      // Nb: I subscriber sono registrati sulla classe quindi non va passato il secondo parametro Name
      $this->dispatcher->dispatch(new ProtocollaPraticaSuccessEvent($pratica));
    } else {
      throw new IncompleteExecutionException();
    }
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

    $rispostaOperatore = $pratica->getRispostaOperatore();

    if ($rispostaOperatore->getNumeroProtocollo() === null) {
      $this->logger->debug(__METHOD__, ['pratica' => $pratica->getId()]);
      $this->handler->sendRispostaToProtocollo($pratica);
      $this->logger->notice('Sending risposta operatore as allegato : id ' . $rispostaOperatore->getId());
    }

    try {
      $this->validateRispostaUploadFile($pratica, $rispostaOperatore);
      $this->handler->sendAllegatoRispostaToProtocollo($pratica, $rispostaOperatore);
    } catch (AlreadyUploadException $e) {
      $this->logger->error($e->getMessage());
    }

    $allegati = $pratica->getAllegatiOperatore();
    foreach ($allegati as $allegato) {
      try {
        $this->validateRispostaUploadFile($pratica, $allegato);
        $this->handler->sendAllegatoRispostaToProtocollo($pratica, $allegato);
      } catch (AlreadyUploadException $e) {
        $this->logger->error($e->getMessage());
      }
    }

    // Predispose document
    if ($this->handler instanceof PredisposedProtocolHandlerInterface && $rispostaOperatore->getNumeroProtocollo() === null) {
      try {
        $this->handler->protocolPredisposedAttachment($pratica, $rispostaOperatore);
      } catch (\Exception $e) {
        $this->logger->error("Errore nella predisposizione della pratica", ['pratica' => $pratica->getId()]);
      }
    }

    $this->entityManager->persist($rispostaOperatore);
    $this->entityManager->persist($pratica);
    $this->entityManager->flush();
    // Nb: I subscriber sono registrati sulla classe quindi non va passato il secondo parametro Name
    $this->dispatcher->dispatch(new ProtocollaAllegatiOperatoreSuccessEvent($pratica));
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
