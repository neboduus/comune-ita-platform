<?php

namespace App\Protocollo;

use App\Entity\AllegatoInterface;
use App\Entity\CPSUser;
use App\Entity\Ente;
use App\Entity\ModuloCompilato;
use App\Entity\Pratica;
use App\Entity\RichiestaIntegrazione;
use App\Entity\RispostaIntegrazione;
use App\Entity\Servizio;
use App\Protocollo\Exception\ResponseErrorException;
use App\Services\FileService\AllegatoFileService;
use App\Utils\StringUtils;
use Exception;
use GuzzleHttp\Client;
use League\Flysystem\FileNotFoundException;

/**
 * @property $instance string
 */
class PiTreProtocolloHandler implements ProtocolloHandlerInterface, PredisposedProtocolHandlerInterface
{
  const IDENTIFIER = 'pitre';

  public function getIdentifier(): string
  {
    return self::IDENTIFIER;
  }

  /**
   * @var Client
   */
  private $client;

  private $instance;
  /**
   * @var AllegatoFileService
   */
  private $fileService;


  /**
   * @param Client $client
   * @param $instance
   * @param AllegatoFileService $fileService
   */
  public function __construct(Client $client, $instance, AllegatoFileService $fileService)
  {
    $this->client = $client;
    $this->instance = $instance;
    $this->fileService = $fileService;
  }

  public function getName(): string
  {
    return 'PiTre';
  }

  public function getExecutionType()
  {
    return self::PROTOCOL_EXECUTION_TYPE_INTERNAL;
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

    if (!empty($pratica->getIdDocumentoProtocollo()) && !empty($pratica->getNumeroFascicolo())) {

      // Todo throw exception?
      return;
    }

    $parameters = $this->getParameters($pratica);
    $parameters->set('method', 'createDocumentPredisposed');
    //$queryString = http_build_query($parameters->all());
    //$response = $this->client->get('?' . $queryString);
    $response = $this->client->post('', ['form_params' => $parameters->all()]);

    $responseData = new PiTreResponseData((array)json_decode((string)$response->getBody(), true));

    if ($responseData->getStatus() == 'success') {
      $pratica->setIdDocumentoProtocollo($responseData->getIdDoc());
      $pratica->setNumeroFascicolo($responseData->getIdProj());
      //$pratica->setNumeroProtocollo($responseData->getNProt());
    } else {
      throw new ResponseErrorException($responseData . ' on request ' . $parameters->toString());
    }
  }

  /**
   * @throws ResponseErrorException
   * @throws Exception
   */
  public function protocolPredisposed(Pratica $pratica)
  {
    $parameters = $this->getParameters($pratica);
    $parameters->set('method', 'protocolPredisposed');
    $parameters->set('documentId', $pratica->getIdDocumentoProtocollo());
    $response = $this->client->post('', ['form_params' => $parameters->all()]);

    $responseData = new PiTreResponseData((array)json_decode((string)$response->getBody(), true));
    if ($responseData->getStatus() == 'success') {
      $pratica->setNumeroProtocollo($responseData->getNProt());
    } else {
      throw new ResponseErrorException($responseData . ' on request ' . $parameters->toString());
    }
  }


  /**
   * @throws ResponseErrorException
   * @throws Exception
   */
  public function protocolPredisposedAttachment(Pratica $pratica, AllegatoInterface $attachment)
  {
    $parameters = $this->getParameters($pratica);
    $parameters->set('method', 'protocolPredisposed');
    $parameters->set('documentId', $attachment->getIdDocumentoProtocollo());
    $response = $this->client->post('', ['form_params' => $parameters->all()]);

    $responseData = new PiTreResponseData((array)json_decode((string)$response->getBody(), true));
    if ($responseData->getStatus() == 'success') {
      $attachment->setNumeroProtocollo($responseData->getNProt());
      $pratica->addNumeroDiProtocollo([
        'id' => $attachment->getId(),
        'protocollo' => $attachment->getIdDocumentoProtocollo(),
      ]);
    } else {
      throw new ResponseErrorException($responseData . ' on request ' . $parameters->toString());
    }
  }

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $allegato
   *
   * @throws ResponseErrorException
   * @throws Exception
   */
  public function sendAllegatoToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
  {
    $parameters = $this->getParameters($pratica, $allegato);
    $parameters->set('method', 'uploadFileToDocument');
    // trasmissionIDArray va valorizzato solo per il metodo createDocumentAndAddInProject
    $parameters->remove('trasmissionIDArray');

    $response = $this->client->post('', ['form_params' => $parameters->all()]);

    $responseData = new PiTreResponseData((array)json_decode((string)$response->getBody(), true));
    if ($responseData->getStatus() == 'success') {
      $pratica->addNumeroDiProtocollo([
        'id' => $allegato->getId(),
        'protocollo' => $responseData->getIdDoc(),
      ]);
    } else {
      throw new ResponseErrorException($responseData . ' on query ' . $parameters->toString());
    }
  }

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $richiesta
   *
   * @throws ResponseErrorException
   * @throws Exception
   */
  public function sendRichiestaIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $richiesta)
  {
    $parameters = $this->getRichiestaIntegrazioneParameters($pratica, $richiesta);
    $parameters->set('method', 'createDocumentPredisposed');
    $response = $this->client->post('', ['form_params' => $parameters->all()]);
    $responseData = new PiTreResponseData((array)json_decode((string)$response->getBody(), true));

    if ($responseData->getStatus() == 'success') {
      $richiesta->setIdDocumentoProtocollo($responseData->getIdDoc());
    } else {
      throw new ResponseErrorException($responseData . ' on query ' . $parameters->toString());
    }
  }

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $richiestaIntegrazione
   * @param AllegatoInterface $allegato
   *
   * @throws ResponseErrorException
   * @throws Exception
   */
  public function sendAllegatoRichiestaIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $richiestaIntegrazione, AllegatoInterface $allegato)
  {
    $parameters = $this->getAllegatoRichiestaIntegrazioneParameters($pratica, $richiestaIntegrazione, $allegato);
    $parameters->set('method', 'uploadFileToDocument');
    // trasmissionIDArray va valorizzato solo per il metodo createDocumentAndAddInProject
    $parameters->remove('trasmissionIDArray');
    $response = $this->client->post('', ['form_params' => $parameters->all()]);
    $responseData = new PiTreResponseData((array)json_decode((string)$response->getBody(), true));

    if ($responseData->getStatus() == 'success') {
      $allegato->setIdDocumentoProtocollo($responseData->getIdDoc());
      // Aggiungo id allegato alla richiesta integrazione
      $richiestaIntegrazione->addNumeroDiProtocollo([
        'id' => $allegato->getId(),
        'protocollo' => $responseData->getIdDoc(),
      ]);

    } else {
      throw new ResponseErrorException($responseData . ' on query ' . $parameters->toString());
    }
  }

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $risposta
   *
   * @throws ResponseErrorException
   * @throws Exception
   */
  public function sendRispostaIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $risposta)
  {
    $parameters = $this->getRispostaIntegrazioneParameters($pratica, $risposta);
    $parameters->set('method', 'createDocumentPredisposed');
    $response = $this->client->post('', ['form_params' => $parameters->all()]);
    $responseData = new PiTreResponseData((array)json_decode((string)$response->getBody(), true));

    if ($responseData->getStatus() == 'success') {
      //$risposta->setNumeroProtocollo($responseData->getNProt());
      $risposta->setIdDocumentoProtocollo($responseData->getIdDoc());
      $pratica->addNumeroDiProtocollo([
        'id' => $risposta->getId(),
        'protocollo' => $responseData->getIdDoc() ?? '',
      ]);
    } else {
      throw new ResponseErrorException($responseData . ' on query ' . $parameters->toString());
    }
  }

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $rispostaIntegrazione
   * @param AllegatoInterface $integrazione
   *
   * @throws ResponseErrorException
   * @throws Exception
   */
  public function sendIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $rispostaIntegrazione, AllegatoInterface $integrazione)
  {
    $parameters = $this->getIntegrazioneParameters($pratica, $rispostaIntegrazione, $integrazione);
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
      throw new ResponseErrorException($responseData . ' on query ' . $parameters->toString());
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
    $parameters->set('method', 'createDocumentPredisposed');

    $response = $this->client->post('', ['form_params' => $parameters->all()]);

    $responseData = new PiTreResponseData((array)json_decode((string)$response->getBody(), true));
    if ($responseData->getStatus() == 'success') {
      //$risposta->setNumeroProtocollo($responseData->getNProt());
      $risposta->setIdDocumentoProtocollo($responseData->getIdDoc());
    } else {
      throw new ResponseErrorException($responseData . ' on query ' . $parameters->toString());
    }
  }

  /**
   * @param Pratica $pratica
   *
   * @throws ResponseErrorException
   * @throws Exception
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
      throw new ResponseErrorException($responseData . ' on query ' . $parameters->toString());
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
    $response = $this->client->post('', ['form_params' => $parameters->all()]);

    $responseData = new PiTreResponseData((array)json_decode((string)$response->getBody(), true));
    if ($responseData->getStatus() == 'success') {
      $risposta->addNumeroDiProtocollo([
        'id' => $allegato->getId(),
        'protocollo' => $responseData->getIdDoc(),
      ]);
    } else {
      throw new ResponseErrorException($responseData . ' on query ' . $parameters->toString());
    }
  }

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface|null $allegato
   * @return PiTreProtocolloParameters
   * @throws FileNotFoundException
   */
  private function getParameters(Pratica $pratica, AllegatoInterface $allegato = null): PiTreProtocolloParameters
  {
    $ente = $pratica->getEnte();
    $servizio = $pratica->getServizio();
    /** @var CPSUser $user */
    $user = $pratica->getUser();

    $parameters = $this->getServizioParameters($servizio, $ente);
    $parameters = new PiTreProtocolloParameters($parameters);


    if (!$parameters->getInstance()) {
      $parameters->setInstance($this->instance);
    }

    if ($allegato instanceof AllegatoInterface) {

      $this->checkFileSize($pratica, $allegato);

      $parameters->setDocumentId($pratica->getIdDocumentoProtocollo());
      $parameters->setFileName(StringUtils::sanitizeFileName($allegato->getOriginalFilename()));
      $fileContent = base64_encode($this->fileService->getAttachmentContent($allegato));
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

      $this->checkFileSize($pratica, $moduloCompilato);

      $parameters->setProjectDescription($pratica->getServizio()->getName() . ' ' . $user->getFullName() . ' ' . $user->getCodiceFiscale());
      $parameters->setDocumentObj($object);
      $parameters->setDocumentDescription($moduloCompilato->getDescription());

      $parameters->setFileName(StringUtils::sanitizeFileName($moduloCompilato->getOriginalFilename()));
      $fileContent = base64_encode($this->fileService->getAttachmentContent($moduloCompilato));
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

  /**
   * @throws FileNotFoundException
   */
  private function getRispostaParameters(Pratica $pratica, AllegatoInterface $allegato = null): PiTreProtocolloParameters
  {
    $risposta = $pratica->getRispostaOperatore();
    $ente = $pratica->getEnte();
    $servizio = $pratica->getServizio();
    /** @var CPSUser $user */
    $user = $pratica->getUser();

    $parameters = $this->getServizioParameters($servizio, $ente);
    $parameters = new PiTreProtocolloParameters($parameters);

    if (!$parameters->getInstance()) {
      $parameters->setInstance($this->instance);
    }

    if ($allegato instanceof AllegatoInterface) {

      $this->checkFileSize($pratica, $allegato);

      $parameters->setDocumentId($risposta->getIdDocumentoProtocollo());
      $parameters->setFileName(StringUtils::sanitizeFileName($allegato->getOriginalFilename()));
      $fileContent = base64_encode($this->fileService->getAttachmentContent($allegato));
      $parameters->setFile($fileContent);
      $parameters->setChecksum(md5($fileContent));

      $parameters->setAttachmentDescription($allegato->getDescription() . ' ' . $user->getFullName() . ' ' . $user->getCodiceFiscale());
    } else {

      $this->checkFileSize($pratica, $risposta);

      $object = $pratica->getServizio()->getName() . ' ' . $user->getFullName() . ' ' . $user->getCodiceFiscale();
      if ($pratica->getOggetto() != null && !empty($pratica->getOggetto())) {
        $object = $pratica->getOggetto() . ' - ' . $user->getFullName() . ' ' . $user->getCodiceFiscale();
      }

      $parameters->setDocumentObj('Risposta ' . $object);
      $parameters->setDocumentDescription('Risposta ' . $pratica->getServizio()->getName() . ' ' . $user->getFullName() . ' ' . $user->getCodiceFiscale());


      $parameters->setFileName(StringUtils::sanitizeFileName($risposta->getOriginalFilename()));
      $fileContent = base64_encode($this->fileService->getAttachmentContent($risposta));
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

  /**
   * @param Pratica $pratica
   * @return PiTreProtocolloParameters
   * @throws FileNotFoundException
   */
  private function getRititroParameters(Pratica $pratica): PiTreProtocolloParameters
  {

    $ritiro = $pratica->getWithdrawAttachment();

    $this->checkFileSize($pratica, $ritiro);

    $ente = $pratica->getEnte();
    $servizio = $pratica->getServizio();
    /** @var CPSUser $user */
    $user = $pratica->getUser();

    $parameters = $this->getServizioParameters($servizio, $ente);
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

    $parameters->setFileName(StringUtils::sanitizeFileName($ritiro->getOriginalFilename()));
    $fileContent = base64_encode($this->fileService->getAttachmentContent($ritiro));
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
   * @param AllegatoInterface $richiesta
   * @return PiTreProtocolloParameters
   * @throws FileNotFoundException
   */
  private function getRichiestaIntegrazioneParameters(Pratica $pratica, AllegatoInterface $richiesta): PiTreProtocolloParameters
  {
    $this->checkFileSize($pratica, $richiesta);

    $ente = $pratica->getEnte();
    $servizio = $pratica->getServizio();
    /** @var CPSUser $user */
    $user = $pratica->getUser();

    $parameters = $this->getServizioParameters($servizio, $ente);
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

    $parameters->setFileName(StringUtils::sanitizeFileName($richiesta->getOriginalFilename()));
    $fileContent = base64_encode($this->fileService->getAttachmentContent($richiesta));
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
   * @param RichiestaIntegrazione $richiestaIntegrazione
   * @param AllegatoInterface $allegato
   * @return PiTreProtocolloParameters
   * @throws FileNotFoundException
   */
  private function getAllegatoRichiestaIntegrazioneParameters(Pratica $pratica, RichiestaIntegrazione $richiestaIntegrazione,  AllegatoInterface $allegato)
  {

    $this->checkFileSize($pratica, $allegato);

    $ente = $pratica->getEnte();
    $servizio = $pratica->getServizio();
    /** @var CPSUser $user */
    $user = $pratica->getUser();

    $parameters = $this->getServizioParameters($servizio, $ente);
    $parameters = new PiTreProtocolloParameters($parameters);

    if (!$parameters->getInstance()) {
      $parameters->setInstance($this->instance);
    }

    $parameters->setDocumentId($richiestaIntegrazione->getIdDocumentoProtocollo());
    $parameters->setFileName(StringUtils::sanitizeFileName($allegato->getOriginalFilename()));
    $fileContent = base64_encode($this->fileService->getAttachmentContent($allegato));
    $parameters->setFile($fileContent);
    $parameters->setChecksum(md5($fileContent));
    $parameters->setAttachmentDescription($allegato->getDescription() . ' ' . $user->getFullName() . ' ' . $user->getCodiceFiscale());

    return $parameters;
  }

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $allegato
   * @return PiTreProtocolloParameters
   * @throws FileNotFoundException
   */
  private function getRispostaIntegrazioneParameters(Pratica $pratica, AllegatoInterface $allegato)
  {

    $this->checkFileSize($pratica, $allegato);

    $ente = $pratica->getEnte();
    $servizio = $pratica->getServizio();
    /** @var CPSUser $user */
    $user = $pratica->getUser();

    $parameters = $this->getServizioParameters($servizio, $ente);
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

    $parameters->setFileName(StringUtils::sanitizeFileName($allegato->getOriginalFilename()));
    $fileContent = base64_encode($this->fileService->getAttachmentContent($allegato));
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
   * @throws FileNotFoundException
   */
  private function getIntegrazioneParameters(Pratica $pratica, RispostaIntegrazione $rispostaIntegrazione,  AllegatoInterface $integrazione)
  {

    $this->checkFileSize($pratica, $integrazione);

    $ente = $pratica->getEnte();
    $servizio = $pratica->getServizio();
    /** @var CPSUser $user */
    $user = $pratica->getUser();

    $parameters = $this->getServizioParameters($servizio, $ente);
    $parameters = new PiTreProtocolloParameters($parameters);

    if (!$parameters->getInstance()) {
      $parameters->setInstance($this->instance);
    }

    $parameters->setDocumentId($rispostaIntegrazione->getIdDocumentoProtocollo());
    $parameters->setFileName(StringUtils::sanitizeFileName($integrazione->getOriginalFilename()));
    $fileContent = base64_encode($this->fileService->getAttachmentContent($integrazione));
    $parameters->setFile($fileContent);
    $parameters->setChecksum(md5($fileContent));
    $parameters->setAttachmentDescription($integrazione->getDescription() . ' ' . $user->getFullName() . ' ' . $user->getCodiceFiscale());

    return $parameters;
  }

  /**
   * @param Servizio $servizio
   * @param Ente $ente
   * @return array
   */
  private function getServizioParameters(Servizio $servizio, Ente $ente): array
  {
    if (!empty($servizio->getProtocolloParameters())) {
      return (array)$servizio->getProtocolloParameters();
    }

    return (array)$ente->getProtocolloParametersPerServizio($servizio);
  }

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $allegato
   * @throws FileNotFoundException
   * @throws Exception
   */
  private function checkFileSize(Pratica $pratica, AllegatoInterface $allegato)
  {
    $data = $this->fileService->getAttachmentData($allegato);
    if ($data['size'] <= 0) {
      throw new Exception('File size error - application: ' .  $pratica->getId() . ' - attachment: ' . $allegato->getId());
    }
  }

}

