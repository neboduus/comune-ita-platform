<?php

namespace App\Protocollo;

use App\Entity\AllegatoInterface;
use App\Entity\ModuloCompilato;
use App\Entity\Pratica;
use App\Protocollo\Exception\ResponseErrorException;
use GuzzleHttp\Client;


class SicrawebProtocolloHandler implements ProtocolloHandlerInterface
{

  /**
   * @var Client
   */
  private $client;

  /** @var string */
  private $instance;

  public function __construct(Client $client, $instance)
  {
    $this->client = $client;
    $this->instance = $instance;
  }

  public function getName()
  {
    return 'SicraWeb';
  }


  public function getConfigParameters()
  {
    return false;
  }

  /**
   * @param Pratica $pratica
   *
   * @throws ResponseErrorException
   */
  public function sendPraticaToProtocollo(Pratica $pratica)
  {
    $parameters = $this->getParameters($pratica);
    $parameters->set('method', 'createDocumentAndAddInProject');
    $queryString = http_build_query($parameters->all());
    $response = $this->client->get('?' . $queryString);
    $responseData = new PiTreResponseData((array)json_decode((string)$response->getBody(), true));
    if ($responseData->getStatus() == 'success') {
      $pratica->setIdDocumentoProtocollo($responseData->getIdDoc());
      $pratica->setNumeroProtocollo($responseData->getNProt());
      $pratica->setNumeroFascicolo($responseData->getIdProj());
    } else {
      throw new ResponseErrorException($responseData . ' on query ' . $queryString);
    }
  }

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $allegato
   *
   * @throws ResponseErrorException
   */
  public function sendAllegatoToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
  {
    $parameters = $this->getParameters($pratica, $allegato);
    $parameters->set('method', 'uploadFileToDocument');
    // trasmissionIDArray vavalorizzato solo per il metodo createDocumentAndAddInProject
    $parameters->remove('trasmissionIDArray');

    $queryString = http_build_query($parameters->all());
    $response = $this->client->get('?' . $queryString);
    $responseData = new PiTreResponseData((array)json_decode((string)$response->getBody(), true));
    if ($responseData->getStatus() == 'success') {
      $pratica->addNumeroDiProtocollo([
        'id' => $allegato->getId(),
        'protocollo' => $responseData->getIdDoc(),
      ]);
    } else {
      throw new ResponseErrorException($responseData . ' on query ' . $queryString);
    }
  }

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $allegato
   * @throws ResponseErrorException
   */
  public function sendRichiestaIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
  {
    $this->sendAllegatoToProtocollo($pratica, $allegato);
  }

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $allegato
   */
  public function sendRispostaIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
  {

  }


  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $rispostaIntegrazione
   * @param AllegatoInterface $allegato
   *
   * @throws ResponseErrorException
   */
  public function sendIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $rispostaIntegrazione, AllegatoInterface $allegato)
  {
    $this->sendAllegatoToProtocollo($pratica, $allegato);
  }


  /**
   * @param Pratica $pratica
   *
   * @throws ResponseErrorException
   */
  public function sendRispostaToProtocollo(Pratica $pratica)
  {
    $risposta = $pratica->getRispostaOperatore();
    $parameters = $this->getRispostaParameters($pratica);
    $parameters->set('method', 'createDocumentAndAddInProject');
    $queryString = http_build_query($parameters->all());
    $response = $this->client->get('?' . $queryString);
    $responseData = new PiTreResponseData((array)json_decode((string)$response->getBody(), true));
    if ($responseData->getStatus() == 'success') {
      $risposta->setNumeroProtocollo($responseData->getNProt());
      $risposta->setIdDocumentoProtocollo($responseData->getIdDoc());
    } else {
      throw new ResponseErrorException($responseData . ' on query ' . $queryString);
    }
  }

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $allegato
   *
   * @throws ResponseErrorException
   */
  public function sendAllegatoRispostaToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
  {
    $risposta = $pratica->getRispostaOperatore();
    $parameters = $this->getRispostaParameters($pratica, $allegato);
    $parameters->set('method', 'uploadFileToDocument');
    // trasmissionIDArray va valorizzato solo in createDocumentAndAddInProject
    $parameters->remove('trasmissionIDArray');

    $queryString = http_build_query($parameters->all());
    $response = $this->client->get('?' . $queryString);
    $responseData = new PiTreResponseData((array)json_decode((string)$response->getBody(), true));
    if ($responseData->getStatus() == 'success') {
      $risposta->addNumeroDiProtocollo([
        'id' => $allegato->getId(),
        'protocollo' => $responseData->getIdDoc(),
      ]);
    } else {
      throw new ResponseErrorException($responseData . ' on query ' . $queryString);
    }
  }

  private function getParameters(Pratica $pratica, AllegatoInterface $allegato = null)
  {
    $ente = $pratica->getEnte();
    $servizio = $pratica->getServizio();
    $parameters = (array)$ente->getProtocolloParametersPerServizio($servizio);
    $parameters = new PiTreProtocolloParameters($parameters);

    if (!$parameters->getInstance()) {
      $parameters->setInstance($this->instance);
    }

    if ($allegato instanceof AllegatoInterface) {
      $parameters->setDocumentId($pratica->getIdDocumentoProtocollo());
      $parameters->setFilePath($allegato->getFile()->getPathname());
      $parameters->setAttachmentDescription($allegato->getDescription());
    } else {
      /** @var ModuloCompilato $moduloCompilato */
      $moduloCompilato = $pratica->getModuliCompilati()->first();
      $parameters->setProjectDescription($pratica->getServizio()->getName() . ' ' . $pratica->getUser()->getFullName());
      $parameters->setDocumentDescription($moduloCompilato->getDescription());
      $parameters->setDocumentObj($pratica->getServizio()->getName());
      $parameters->setFilePath($moduloCompilato->getFile()->getPathname());
      $parameters->setCreateProject(true);
    }

    return $parameters;
  }

  private function getRispostaParameters(Pratica $pratica, AllegatoInterface $allegato = null)
  {
    $risposta = $pratica->getRispostaOperatore();
    $ente = $pratica->getEnte();
    $servizio = $pratica->getServizio();
    $parameters = (array)$ente->getProtocolloParametersPerServizio($servizio);
    $parameters = new PiTreProtocolloParameters($parameters);

    if (!$parameters->getInstance()) {
      $parameters->setInstance($this->instance);
    }

    if ($allegato instanceof AllegatoInterface) {
      $parameters->setDocumentId($risposta->getIdDocumentoProtocollo());
      $parameters->setFilePath($allegato->getFile()->getPathname());
      $parameters->setAttachmentDescription($allegato->getDescription());
    } else {
      // FIXME: translate risposta
      $parameters->setDocumentDescription('Risposta ' . $risposta->getDescription());
      $parameters->setDocumentObj('Risposta ' . $pratica->getServizio()->getName());
      $parameters->setFilePath($risposta->getFile()->getPathname());
      $parameters->setIdProject($pratica->getNumeroFascicolo());
      $parameters->setCreateProject(false);
    }

    return $parameters;
  }

  /**
   * @param Pratica $pratica
   */
  public function sendRitiroToProtocollo(Pratica $pratica)
  {
    // TODO: Implement sendRitiroToProtocollo() method.
  }

}
