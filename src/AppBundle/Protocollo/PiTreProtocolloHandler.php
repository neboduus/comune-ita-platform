<?php

namespace AppBundle\Protocollo;

use AppBundle\Entity\AllegatoInterface;
use AppBundle\Entity\CPSUser;
use AppBundle\Entity\ModuloCompilato;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\RispostaIntegrazione;
use AppBundle\Protocollo\Exception\ResponseErrorException;
use GuzzleHttp\Client;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * @property $instance string
 */
class PiTreProtocolloHandler implements ProtocolloHandlerInterface
{
  /**
   * @var Client
   */
  private $client;

  public function __construct(Client $client, $instance)
  {
    $this->client = $client;
    $this->instance = $instance;
  }

  public function getConfigParameters()
  {
    return PiTreProtocolloParameters::getEnteParametersKeys();
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
    //$queryString = http_build_query($parameters->all());
    //$response = $this->client->get('?' . $queryString);
    $response = $this->client->post('', ['form_params' => $parameters->all()]);

    $responseData = new PiTreResponseData((array)json_decode((string)$response->getBody(), true));
    if ($responseData->getStatus() == 'success') {
      $pratica->setIdDocumentoProtocollo($responseData->getIdDoc());
      $pratica->setNumeroProtocollo($responseData->getNProt());
      $pratica->setNumeroFascicolo($responseData->getIdProj());
    } else {
      throw new ResponseErrorException($responseData . ' on request ' . json_encode($parameters->all()));
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
    // trasmissionIDArray va valorizzato solo per il metodo createDocumentAndAddInProject
    $parameters->remove('trasmissionIDArray');

    //$queryString = http_build_query($parameters->all());
    //$response = $this->client->get('?' . $queryString);

    $response = $this->client->post('', ['form_params' => $parameters->all()]);

    $responseData = new PiTreResponseData((array)json_decode((string)$response->getBody(), true));
    if ($responseData->getStatus() == 'success') {
      $pratica->addNumeroDiProtocollo([
        'id' => $allegato->getId(),
        'protocollo' => $responseData->getIdDoc(),
      ]);
    } else {
      throw new ResponseErrorException($responseData . ' on query ' . json_encode($parameters->all()));
    }
  }

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $richiesta
   *
   * @throws ResponseErrorException
   */
  public function sendRichiestaIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $richiesta)
  {
    $parameters = $this->getRichiestaIntegrazioneParameters($pratica, $richiesta);
    $parameters->set('method', 'createDocumentAndAddInProject');

    //$queryString = http_build_query($parameters->all());
    //$response = $this->client->get('?' . $queryString);
    $response = $this->client->post('', ['form_params' => $parameters->all()]);

    $responseData = new PiTreResponseData((array)json_decode((string)$response->getBody(), true));

    if ($responseData->getStatus() == 'success') {
      $richiesta->setNumeroProtocollo($responseData->getNProt());
      $richiesta->setIdDocumentoProtocollo($responseData->getIdDoc());
      $pratica->addNumeroDiProtocollo([
        'id' => $richiesta->getId(),
        'protocollo' => $responseData->getIdDoc() ?? '',
      ]);
    } else {
      throw new ResponseErrorException($responseData . ' on query ' . json_encode($parameters->all()));
    }
  }

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $risposta
   *
   * @throws ResponseErrorException
   */
  public function sendRispostaIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $risposta)
  {
    $parameters = $this->getRispostaIntegrazioneParameters($pratica, $risposta);
    $parameters->set('method', 'createDocumentAndAddInProject');
    $response = $this->client->post('', ['form_params' => $parameters->all()]);
    $responseData = new PiTreResponseData((array)json_decode((string)$response->getBody(), true));

    if ($responseData->getStatus() == 'success') {
      $risposta->setNumeroProtocollo($responseData->getNProt());
      $risposta->setIdDocumentoProtocollo($responseData->getIdDoc());
      $pratica->addNumeroDiProtocollo([
        'id' => $risposta->getId(),
        'protocollo' => $responseData->getIdDoc() ?? '',
      ]);
    } else {
      throw new ResponseErrorException($responseData . ' on query ' . json_encode($parameters->all()));
    }
  }

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $rispostaIntegrazione
   * @param AllegatoInterface $integrazione
   *
   * @throws ResponseErrorException
   */
  public function sendIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $rispostaIntegrazione, AllegatoInterface $integrazione)
  {
    $parameters = $this->getIntegrazioneParameters($pratica, $rispostaIntegrazione, $integrazione);
    $parameters->set('method', 'createDocumentAndAddInProject');
    $parameters->set('method', 'uploadFileToDocument');
    // trasmissionIDArray va valorizzato solo per il metodo createDocumentAndAddInProject
    $parameters->remove('trasmissionIDArray');
    $response = $this->client->post('', ['form_params' => $parameters->all()]);
    $responseData = new PiTreResponseData((array)json_decode((string)$response->getBody(), true));

    if ($responseData->getStatus() == 'success') {
      $integrazione->setIdDocumentoProtocollo($responseData->getIdDoc());
      // Aggiungo id allegato alla pratica
      $pratica->addNumeroDiProtocollo([
        'id' => $integrazione->getId(),
        'protocollo' => $responseData->getIdDoc(),
      ]);

    } else {
      throw new ResponseErrorException($responseData . ' on query ' . json_encode($parameters->all()));
    }
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

    //$queryString = http_build_query($parameters->all());
    //$response = $this->client->get('?' . $queryString);
    $response = $this->client->post('', ['form_params' => $parameters->all()]);

    $responseData = new PiTreResponseData((array)json_decode((string)$response->getBody(), true));
    if ($responseData->getStatus() == 'success') {
      $risposta->setNumeroProtocollo($responseData->getNProt());
      $risposta->setIdDocumentoProtocollo($responseData->getIdDoc());
    } else {
      throw new ResponseErrorException($responseData . ' on query ' . json_encode($parameters->all()));
    }
  }

  /**
   * @param Pratica $pratica
   *
   * @throws ResponseErrorException
   */
  public function sendRitiroToProtocollo(Pratica $pratica)
  {
    $withdrawAttachment = $pratica->getWithdrawAttachment();
    $parameters = $this->getRititroParameters($pratica);
    $parameters->set('method', 'createDocumentAndAddInProject');
    $response = $this->client->post('', ['form_params' => $parameters->all()]);

    $responseData = new PiTreResponseData((array)json_decode((string)$response->getBody(), true));
    if ($responseData->getStatus() == 'success') {
      $withdrawAttachment->setNumeroProtocollo($responseData->getNProt());
      $withdrawAttachment->setIdDocumentoProtocollo($responseData->getIdDoc());
    } else {
      throw new ResponseErrorException($responseData . ' on query ' . json_encode($parameters->all()));
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

    //$queryString = http_build_query($parameters->all());
    //$response = $this->client->get('?' . $queryString);
    $response = $this->client->post('', ['form_params' => $parameters->all()]);

    $responseData = new PiTreResponseData((array)json_decode((string)$response->getBody(), true));
    if ($responseData->getStatus() == 'success') {
      $risposta->addNumeroDiProtocollo([
        'id' => $allegato->getId(),
        'protocollo' => $responseData->getIdDoc(),
      ]);
    } else {
      throw new ResponseErrorException($responseData . ' on query ' . json_encode($parameters->all()));
    }
  }

  private function getParameters(Pratica $pratica, AllegatoInterface $allegato = null)
  {
    $ente = $pratica->getEnte();
    $servizio = $pratica->getServizio();
    /** @var CPSUser $user */
    $user = $pratica->getUser();

    $parameters = (array)$ente->getProtocolloParametersPerServizio($servizio);
    $parameters = new PiTreProtocolloParameters($parameters);


    if (!$parameters->getInstance()) {
      $parameters->setInstance($this->instance);
    }

    if ($allegato instanceof AllegatoInterface) {
      $parameters->setDocumentId($pratica->getIdDocumentoProtocollo());
      //$parameters->setFilePath($allegato->getFile()->getPathname());
      $path = $allegato->getFile()->getPathname();
      $parameters->setFileName($allegato->getFile()->getFilename());
      $fileContent = base64_encode(file_get_contents($path));
      $parameters->setFile($fileContent);
      $parameters->setChecksum(md5($fileContent));

      $parameters->setAttachmentDescription($allegato->getDescription() . ' ' . $user->getFullName() . ' ' . $user->getCodiceFiscale());
    } else {

      $object = $pratica->getServizio()->getName() . ' ' . $user->getFullName() . ' ' . $user->getCodiceFiscale();
      if ($pratica->getOggetto() != null && !empty($pratica->getOggetto())) {
        $object = $pratica->getOggetto() . ' - ' . $user->getFullName() . ' ' . $user->getCodiceFiscale();
      }

      /** @var ModuloCompilato $moduloCompilato */
      $moduloCompilato = $pratica->getModuliCompilati()->first();
      $parameters->setProjectDescription($pratica->getServizio()->getName() . ' ' . $user->getFullName() . ' ' . $user->getCodiceFiscale());

      $parameters->setDocumentObj($object);
      $parameters->setDocumentDescription($moduloCompilato->getDescription());

      //$parameters->setFilePath($moduloCompilato->getFile()->getPathname());
      $path = $moduloCompilato->getFile()->getPathname();
      $parameters->setFileName($moduloCompilato->getFile()->getFilename());
      $fileContent = base64_encode(file_get_contents($path));
      $parameters->setFile($fileContent);
      $parameters->setChecksum(md5($fileContent));

      $parameters->setCreateProject(true);
      $parameters->setDocumentType("A");

      $parameters->setSenderName($user->getNome());
      $parameters->setSenderSurname($user->getCognome());
      $parameters->setSenderCf($user->getCodiceFiscale());
      $parameters->setSenderEmail($user->getEmail());
    }

    return $parameters;
  }

  private function getRispostaParameters(Pratica $pratica, AllegatoInterface $allegato = null)
  {
    $risposta = $pratica->getRispostaOperatore();
    $ente = $pratica->getEnte();
    $servizio = $pratica->getServizio();
    /** @var CPSUser $user */
    $user = $pratica->getUser();

    $parameters = (array)$ente->getProtocolloParametersPerServizio($servizio);
    $parameters = new PiTreProtocolloParameters($parameters);

    if (!$parameters->getInstance()) {
      $parameters->setInstance($this->instance);
    }

    if ($allegato instanceof AllegatoInterface) {
      $parameters->setDocumentId($risposta->getIdDocumentoProtocollo());
      //$parameters->setFilePath($allegato->getFile()->getPathname());
      $path = $allegato->getFile()->getPathname();
      $parameters->setFileName($allegato->getFile()->getFilename());
      $fileContent = base64_encode(file_get_contents($path));
      $parameters->setFile($fileContent);
      $parameters->setChecksum(md5($fileContent));

      $parameters->setAttachmentDescription($allegato->getDescription() . ' ' . $user->getFullName() . ' ' . $user->getCodiceFiscale());
    } else {

      $object = $pratica->getServizio()->getName() . ' ' . $user->getFullName() . ' ' . $user->getCodiceFiscale();
      if ($pratica->getOggetto() != null && !empty($pratica->getOggetto())) {
        $object = $pratica->getOggetto() . ' - ' . $user->getFullName() . ' ' . $user->getCodiceFiscale();
      }

      $parameters->setDocumentObj('Risposta ' . $object);
      $parameters->setDocumentDescription('Risposta ' . $pratica->getServizio()->getName() . ' ' . $user->getFullName() . ' ' . $user->getCodiceFiscale());

      //$parameters->setFilePath($risposta->getFile()->getPathname());
      $path = $risposta->getFile()->getPathname();
      $parameters->setFileName($risposta->getFile()->getFilename());
      $fileContent = base64_encode(file_get_contents($path));
      $parameters->setFile($fileContent);
      $parameters->setChecksum(md5($fileContent));

      $parameters->setIdProject($pratica->getNumeroFascicolo());
      $parameters->setCreateProject(false);
      $parameters->setDocumentType("P");

      $parameters->setSenderName($user->getNome());
      $parameters->setSenderSurname($user->getCognome());
      $parameters->setSenderCf($user->getCodiceFiscale());
      $parameters->setSenderEmail($user->getEmail());
    }

    return $parameters;
  }

  private function getRititroParameters(Pratica $pratica)
  {

    $ritiro = $pratica->getWithdrawAttachment();
    $ente = $pratica->getEnte();
    $servizio = $pratica->getServizio();
    /** @var CPSUser $user */
    $user = $pratica->getUser();

    $parameters = (array)$ente->getProtocolloParametersPerServizio($servizio);
    $parameters = new PiTreProtocolloParameters($parameters);

    if (!$parameters->getInstance()) {
      $parameters->setInstance($this->instance);
    }

    $object = $pratica->getServizio()->getName() . ' ' . $user->getFullName() . ' ' . $user->getCodiceFiscale();
    if ($pratica->getOggetto() != null && !empty($pratica->getOggetto())) {
      $object = $pratica->getOggetto() . ' - ' . $user->getFullName() . ' ' . $user->getCodiceFiscale();
    }

    $parameters->setDocumentObj('Ritiro ' . $object);
    $parameters->setDocumentDescription('Ritiro ' . $pratica->getServizio()->getName() . ' ' . $user->getFullName() . ' ' . $user->getCodiceFiscale());

    $path = $ritiro->getFile()->getPathname();
    $parameters->setFileName($ritiro->getFile()->getFilename());
    $fileContent = base64_encode(file_get_contents($path));
    $parameters->setFile($fileContent);
    $parameters->setChecksum(md5($fileContent));

    $parameters->setIdProject($pratica->getNumeroFascicolo());
    $parameters->setCreateProject(false);
    $parameters->setDocumentType("A");

    $parameters->setSenderName($user->getNome());
    $parameters->setSenderSurname($user->getCognome());
    $parameters->setSenderCf($user->getCodiceFiscale());
    $parameters->setSenderEmail($user->getEmail());

    return $parameters;
  }

  private function getRichiestaIntegrazioneParameters(Pratica $pratica, AllegatoInterface $richiesta)
  {
    $ente = $pratica->getEnte();
    $servizio = $pratica->getServizio();
    /** @var CPSUser $user */
    $user = $pratica->getUser();

    $parameters = (array)$ente->getProtocolloParametersPerServizio($servizio);
    $parameters = new PiTreProtocolloParameters($parameters);

    if (!$parameters->getInstance()) {
      $parameters->setInstance($this->instance);
    }

    $object = $pratica->getServizio()->getName() . ' ' . $user->getFullName() . ' ' . $user->getCodiceFiscale();
    if ($pratica->getOggetto() != null && !empty($pratica->getOggetto())) {
      $object = $pratica->getOggetto() . ' - ' . $user->getFullName() . ' ' . $user->getCodiceFiscale();
    }

    $parameters->setDocumentObj('Richiesta integrazione ' . $object);
    $parameters->setDocumentDescription('Richiesta integrazione ' . $pratica->getServizio()->getName() . ' ' . $user->getFullName() . ' ' . $user->getCodiceFiscale());

    //$parameters->setFilePath($richiesta->getFile()->getPathname());
    $path = $richiesta->getFile()->getPathname();
    $parameters->setFileName($richiesta->getFile()->getFilename());
    $fileContent = base64_encode(file_get_contents($path));
    $parameters->setFile($fileContent);
    $parameters->setChecksum(md5($fileContent));

    $parameters->setIdProject($pratica->getNumeroFascicolo());
    $parameters->setCreateProject(false);
    $parameters->setDocumentType("P");

    $parameters->setSenderName($user->getNome());
    $parameters->setSenderSurname($user->getCognome());
    $parameters->setSenderCf($user->getCodiceFiscale());
    $parameters->setSenderEmail($user->getEmail());

    return $parameters;
  }

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $allegato
   * @return PiTreProtocolloParameters
   */
  private function getRispostaIntegrazioneParameters(Pratica $pratica, AllegatoInterface $allegato)
  {
    $ente = $pratica->getEnte();
    $servizio = $pratica->getServizio();
    /** @var CPSUser $user */
    $user = $pratica->getUser();

    $parameters = (array)$ente->getProtocolloParametersPerServizio($servizio);
    $parameters = new PiTreProtocolloParameters($parameters);

    if (!$parameters->getInstance()) {
      $parameters->setInstance($this->instance);
    }

    $object = $pratica->getServizio()->getName() . ' ' . $user->getFullName() . ' ' . $user->getCodiceFiscale();
    if ($pratica->getOggetto() != null && !empty($pratica->getOggetto())) {
      $object = $pratica->getOggetto() . ' - ' . $user->getFullName() . ' ' . $user->getCodiceFiscale();
    }

    $parameters->setDocumentObj('Risposta integrazione ' . $object);
    $parameters->setDocumentDescription('Risposta integrazione ' . $pratica->getServizio()->getName() . ' ' . $user->getFullName() . ' ' . $user->getCodiceFiscale());

    //$parameters->setFilePath($richiesta->getFile()->getPathname());
    $path = $allegato->getFile()->getPathname();
    $parameters->setFileName($allegato->getFile()->getFilename());
    $fileContent = base64_encode(file_get_contents($path));
    $parameters->setFile($fileContent);
    $parameters->setChecksum(md5($fileContent));

    $parameters->setIdProject($pratica->getNumeroFascicolo());
    $parameters->setCreateProject(false);
    $parameters->setDocumentType("A");

    $parameters->setSenderName($user->getNome());
    $parameters->setSenderSurname($user->getCognome());
    $parameters->setSenderCf($user->getCodiceFiscale());
    $parameters->setSenderEmail($user->getEmail());

    return $parameters;
  }

  /**
   * @param Pratica $pratica
   * @param RispostaIntegrazione $rispostaIntegrazione
   * @param AllegatoInterface $integrazione
   * @return PiTreProtocolloParameters
   */
  private function getIntegrazioneParameters(Pratica $pratica, RispostaIntegrazione $rispostaIntegrazione,  AllegatoInterface $integrazione)
  {
    $ente = $pratica->getEnte();
    $servizio = $pratica->getServizio();
    /** @var CPSUser $user */
    $user = $pratica->getUser();

    $parameters = (array)$ente->getProtocolloParametersPerServizio($servizio);
    $parameters = new PiTreProtocolloParameters($parameters);

    if (!$parameters->getInstance()) {
      $parameters->setInstance($this->instance);
    }

    $parameters->setDocumentId($rispostaIntegrazione->getIdDocumentoProtocollo());
    $path = $integrazione->getFile()->getPathname();
    $parameters->setFileName($integrazione->getFile()->getFilename());
    $fileContent = base64_encode(file_get_contents($path));
    $parameters->setFile($fileContent);
    $parameters->setChecksum(md5($fileContent));
    $parameters->setAttachmentDescription($integrazione->getDescription() . ' ' . $user->getFullName() . ' ' . $user->getCodiceFiscale());

    return $parameters;
  }

}

