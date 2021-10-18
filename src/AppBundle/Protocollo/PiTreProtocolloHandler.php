<?php

namespace AppBundle\Protocollo;

use AppBundle\Entity\AllegatoInterface;
use AppBundle\Entity\CPSUser;
use AppBundle\Entity\Ente;
use AppBundle\Entity\ModuloCompilato;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\RispostaIntegrazione;
use AppBundle\Entity\Servizio;
use AppBundle\Protocollo\Exception\ResponseErrorException;
use AppBundle\Services\FileService;
use GuzzleHttp\Client;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\All;

/**
 * @property $instance string
 */
class PiTreProtocolloHandler implements ProtocolloHandlerInterface, PredisposedProtocolHandlerInterface
{
  /**
   * @var Client
   */
  private $client;

  private $instance;
  /**
   * @var FileService
   */
  private $fileService;


  /**
   * @param Client $client
   * @param $instance
   * @param FileService $fileService
   */
  public function __construct(Client $client, $instance, FileService $fileService)
  {
    $this->client = $client;
    $this->instance = $instance;
    $this->fileService = $fileService;
  }

  public function getName()
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

    $this->protocolPredisposedAttachment($pratica, $richiesta);
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
      throw new ResponseErrorException($responseData . ' on query ' . $parameters->toString());
    }
  }

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface|null $allegato
   * @return PiTreProtocolloParameters
   * @throws \Exception
   */
  private function getParameters(Pratica $pratica, AllegatoInterface $allegato = null)
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
      $parameters->setFileName($allegato->getFile()->getFilename());
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

      $parameters->setFileName($moduloCompilato->getFile()->getFilename());
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

  private function getRispostaParameters(Pratica $pratica, AllegatoInterface $allegato = null)
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
      $parameters->setFileName($allegato->getFile()->getFilename());
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


      $parameters->setFileName($risposta->getFile()->getFilename());
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
   * @throws \Exception
   */
  private function getRititroParameters(Pratica $pratica)
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

    $parameters->setFileName($ritiro->getFile()->getFilename());
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
   * @throws \Exception
   */
  private function getRichiestaIntegrazioneParameters(Pratica $pratica, AllegatoInterface $richiesta)
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

    $parameters->setFileName($richiesta->getFile()->getFilename());
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
   * @param AllegatoInterface $allegato
   * @return PiTreProtocolloParameters
   * @throws \Exception
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

    $parameters->setFileName($allegato->getFile()->getFilename());
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
   * @throws \Exception
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
    $parameters->setFileName($integrazione->getFile()->getFilename());
    $fileContent = base64_encode($this->fileService->getAttachmentContent($rispostaIntegrazione));
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
  private function getServizioParameters(Servizio $servizio, Ente $ente)
  {
    if (!empty($servizio->getProtocolloParameters())) {
      return (array)$servizio->getProtocolloParameters();
    }

    return (array)$ente->getProtocolloParametersPerServizio($servizio);
  }

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $allegato
   * @throws \Exception
   */
  private function checkFileSize(Pratica $pratica, AllegatoInterface $allegato)
  {
    $data = $this->fileService->getAttachmentData($allegato);
    if ($data['size'] <= 0) {
      throw new \Exception('File size error - application: ' .  $pratica->getId() . ' - attachment: ' . $allegato->getId());
    }
  }

}

