<?php

namespace App\Protocollo;

use App\Entity\AllegatoInterface;
use App\Entity\Ente;
use App\Entity\IscrizioneRegistroAssociazioni;
use App\Entity\OccupazioneSuoloPubblico;
use App\Entity\Pratica;
use App\Model\DefaultProtocolSettings;
use App\Services\FileService\AllegatoFileService;
use Doctrine\ORM\EntityManagerInterface;
use DOMDocument;
use League\Flysystem\FileNotFoundException;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;

/**
 * Class InforProtocolloHandler
 * @package App\Protocollo
 *
 *
 * Infor positive response
 * <?xml version="1.0" ?>
 * <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:ns1="http://webservices.jprotocollo.jente.infor.arezzo.it/">
 * <soapenv:Body>
 *  <ns1:inserisciPartenzaResponse>
 *   <rispostaProtocolla>
 *    <ns1:esito>OK</ns1:esito>
 *    <ns1:segnatura>
 *     <ns1:registro>
 *      <ns1:codice>GE</ns1:codice>
 *      <ns1:descrizione>REGISTRO GENERALE</ns1:descrizione>
 *     </ns1:registro>
 *     <ns1:sezione>
 *      <ns1:codice>GE</ns1:codice>
 *      <ns1:descrizione>SEZIONE GENERALE</ns1:descrizione>
 *     </ns1:sezione>
 *     <ns1:anno>2018</ns1:anno>
 *     <ns1:numero>50</ns1:numero>
 *     <ns1:data>14/11/2018</ns1:data>
 *     <ns1:ora>18:37</ns1:ora>
 *     <ns1:amministrazione>
 *      <ns1:ente>
 *       <ns1:codice>c_h612</ns1:codice>
 *       <ns1:descrizione>Comune di Rovereto</ns1:descrizione>
 *      </ns1:ente>
 *      <ns1:aoo>
 *       <ns1:codice>c_h612</ns1:codice>
 *       <ns1:descrizione>Comune di Rovereto</ns1:descrizione>
 *      </ns1:aoo>
 *     </ns1:amministrazione>
 *    </ns1:segnatura>
 *   </rispostaProtocolla>
 *  </ns1:inserisciPartenzaResponse>
 * </soapenv:Body>
 * </soapenv:Envelope>
 */
class InforProtocolloHandler implements ProtocolloHandlerInterface
{
  const IDENTIFIER = 'infor';

  public function getIdentifier(): string
  {
    return self::IDENTIFIER;
  }

  const ACTION_ALLEGA_DOCUMENTO = 'allegaDocumento';
  const ACTION_INSERISCI_ARRIVO = 'inserisciArrivo';
  const ACTION_INSERISCI_PARTENZA = 'inserisciPartenza';

  private $em;
  private $logger;

  private $tempPemFile = false;
  private $tempKeyFile = false;
  /**
   * @var AllegatoFileService
   */
  private $fileService;

  /**
   * @param EntityManagerInterface $em
   * @param LoggerInterface $logger
   * @param AllegatoFileService $fileService
   */
  public function __construct(EntityManagerInterface $em, LoggerInterface $logger, AllegatoFileService $fileService)
  {
    $this->em = $em;
    $this->logger = $logger;
    $this->fileService = $fileService;
  }

  public function getName()
  {
    return 'Infor';
  }

  public function getExecutionType()
  {
    return self::PROTOCOL_EXECUTION_TYPE_INTERNAL;
  }

  public function getConfigParameters()
  {
    /*
     "arrivo": {
        "tipo_documento": "CW",
        "tramite": "WEB",
        "smistamento": "STATO CIVILE",
        "classifica": "11.01",
        "fascicolo": "2017/6"
      },
      "risposta": {
        "mittente interno": "STANZA DEL CITTADINO",
        "tipo_documento": "CW",
        "tramite": "WEB",
        "smistamento": "STATO CIVILE",
        "classifica": "11.01",
        "fascicolo": "2017/6"
      }
     */
    return array(
      'infor_username',
      'infor_denominazione',
      'infor_email',
      'infor_wsdl',
      'infor_wsUrl',
      'arrivo' => [
        'tipo_documento',
        'tramite',
        'smistamento',
        'classifica',
        'fascicolo'
      ],
      'risposta' => [
        'mittente_interno',
        'tipo_documento',
        'tramite',
        'smistamento',
        'classifica',
        'fascicolo',
      ]
    );
  }

  /**
   * @param Pratica $pratica
   *
   */
  public function sendPraticaToProtocollo(Pratica $pratica)
  {
    $parameters = $this->retrieveProtocolloParametersForEnteAndServizio($pratica);

    $xml = new SimpleXMLElement('<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:web="http://webservices.jprotocollo.jente.infor.arezzo.it/"/>');
    $xml->addChild('soapenv:Header');
    $body = $xml->addChild('soapenv:Body');
    $function = $body->addChild('web:inserisciArrivo', null, 'web');
    $richiesta = $function->addChild('richiestaProtocollaArrivo', null, '');
    $richiesta->addChild('web:username', $parameters['infor_username'], 'web');
    $protocollaArrivo = $richiesta->addChild('web:protocollaArrivo', null, 'web');

    $this->createClassificazioneNode($protocollaArrivo, $parameters['arrivo']);

    $this->createAltriDatiNode($protocollaArrivo, $parameters['arrivo']);

    $soggetti = $protocollaArrivo->addChild('web:soggetti', null, 'web');
    $soggetto = $soggetti->addChild('web:soggetto', null, 'web');

    $user = $pratica->getUser();
    $soggetto->addChild('web:denominazione', $user->getFullName() . ' ' . substr(trim($user->getCodiceFiscale()), 0, 16), 'web');

    if ($user->getEmail()) {
      $soggetto->addChild('web:indirizzo', $user->getEmail(), 'web');
    }

    $this->createSmistamentoNode($protocollaArrivo, $parameters['arrivo']);
    $protocollaArrivo->addChild('web:oggetto', $this->retrieveOggettoFromPratica($pratica), 'web');

    $request = trim(str_replace(array('<?xml version="1.0"?>', ' xmlns:web="web"', ' xmlns=""'), '', $xml->asXML()));
    $response = $this->sendRequest(self::ACTION_INSERISCI_ARRIVO, $request, $parameters['infor_wsUrl'], $pratica->getEnte());

    $pratica->setNumeroProtocollo($response);
  }

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $allegato
   * @throws \Exception
   */
  public function sendAllegatoToProtocollo(Pratica $pratica, AllegatoInterface $allegato): void
  {
    $parameters = $this->retrieveProtocolloParametersForEnteAndServizio($pratica);

    $request = $this->createSendAllegatoRequestBody($pratica->getNumeroProtocollo(), $allegato, $parameters);

    $response = $this->sendRequest(self::ACTION_ALLEGA_DOCUMENTO, $request, $parameters['infor_wsUrl'], $pratica->getEnte());

    $pratica->addNumeroDiProtocollo([
      'id' => $allegato->getId(),
      'protocollo' => $response,
    ]);
  }

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $allegato
   * @throws \Exception
   */
  public function sendRispostaIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
  {
    $parameters = $this->retrieveProtocolloParametersForEnteAndServizio($pratica);
    $request = $this->createSendAllegatoRequestBody($pratica->getNumeroProtocollo(), $allegato, $parameters);
    $response = $this->sendRequest(self::ACTION_ALLEGA_DOCUMENTO, $request, $parameters['infor_wsUrl'], $pratica->getEnte());

    $pratica->addNumeroDiProtocollo([
      'id' => $allegato->getId(),
      'protocollo' => $response,
    ]);
  }


  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $rispostaIntegrazione
   * @param AllegatoInterface $allegato
   */
  public function sendIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $rispostaIntegrazione, AllegatoInterface $allegato)
  {
    $this->sendAllegatoToProtocollo($pratica, $allegato);
  }


  /**
   * @param Pratica $pratica
   */
  public function sendRispostaToProtocollo(Pratica $pratica)
  {

    $parameters = $this->retrieveProtocolloParametersForEnteAndServizio($pratica);

    $risposta = $pratica->getRispostaOperatore();
    $xml = new SimpleXMLElement('<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:web="http://webservices.jprotocollo.jente.infor.arezzo.it/"/>');
    $xml->addChild('soapenv:Header');
    $body = $xml->addChild('soapenv:Body');
    $function = $body->addChild('web:inserisciPartenza', null, 'web');
    $richiesta = $function->addChild('richiestaProtocollaPartenza', null, '');
    $richiesta->addChild('web:username', $parameters['infor_username'], 'web');
    $protocollaPartenza = $richiesta->addChild('web:protocollaPartenza', null, 'web');
    $soggetti = $protocollaPartenza->addChild('web:soggetti', null, 'web');
    $soggetto = $soggetti->addChild('web:soggetto', null, 'web');

    $user = $pratica->getUser();
    $soggetto->addChild('web:denominazione', $user->getFullName() . ' ' . substr(trim($user->getCodiceFiscale()), 0, 16), 'web');
    $soggetto->addChild('web:indirizzo', $user->getEmail(), 'web');

    $this->createClassificazioneNode($protocollaPartenza, $parameters['risposta']);
    $this->createAltriDatiNode($protocollaPartenza, $parameters['risposta']);
    $this->createSmistamentoNode($protocollaPartenza, $parameters['risposta']);

    $protocollaPartenza->addChild('web:oggetto', $this->retrieveOggettoRispostaFromPratica($pratica), 'web');
    $request = trim(str_replace(array('<?xml version="1.0"?>', ' xmlns:web="web"', ' xmlns=""'), '', $xml->asXML()));
    $response = $this->sendRequest(self::ACTION_INSERISCI_PARTENZA, $request, $parameters['infor_wsUrl'], $pratica->getEnte());
    $risposta->setNumeroProtocollo($response);
    $this->em->persist($risposta);
    $this->em->flush();
  }

  public function sendRitiroToProtocollo(Pratica $pratica)
  {
    // TODO: Implement sendRitiroToProtocollo() method.
  }


  public function sendAllegatoRispostaToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
  {
    $parameters = $this->retrieveProtocolloParametersForEnteAndServizio($pratica);
    $risposta = $pratica->getRispostaOperatore();

    $request = $this->createSendAllegatoRequestBody($risposta->getNumeroProtocollo(), $allegato, $parameters);

    $response = $this->sendRequest(self::ACTION_ALLEGA_DOCUMENTO, $request, $parameters['infor_wsUrl'], $pratica->getEnte());

    $risposta->addNumeroDiProtocollo([
      'id' => $allegato->getId(),
      'protocollo' => $response,
    ]);
  }

  /**
   * @param $action
   * @param $request
   * @param $url
   * @param Ente|null $ente
   * @return string
   * @throws \Exception
   */
  private function sendRequest($action, $request, $url, Ente $ente = null)
  {
    $header = array(
      'Content-type: text/xml;charset="utf-8"',
      'Accept: text/xml',
      'Cache-Control: no-cache',
      'Pragma: no-cache',
      'SOAPAction: ' . $action,
      'Content-length: ' . strlen($request),
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

    if ($ente != null && $ente->getDefaultProtocolSettings() != null) {
      $settings = DefaultProtocolSettings::fromArray($ente->getDefaultProtocolSettings());
      if ( $settings->getCertificate() != null ) {
        if (!$this->tempPemFile) {
          $this->tempPemFile = tmpfile();
          fwrite($this->tempPemFile, $settings->getCertificate());
        }
        $tempPemPath = stream_get_meta_data($this->tempPemFile);
        $tempPemPath = $tempPemPath['uri'];
        curl_setopt($ch, CURLOPT_SSLCERT, $tempPemPath);
      }

      // Modifica temporanea
      if ( $settings->getCertificateKey() != null ) {
        if (!$this->tempKeyFile) {
          $this->tempKeyFile = tmpfile();
          fwrite($this->tempKeyFile, $settings->getCertificateKey());
        }
        $tempKeyPath = stream_get_meta_data($this->tempKeyFile);
        $tempKeyPath = $tempKeyPath['uri'];
        curl_setopt($ch, CURLOPT_SSLKEY, $tempKeyPath);
      }

      if ( $settings->getCertificatePassword() != null ) {
        curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $settings->getCertificatePassword());
      }
    }

    $this->logger->notice('Sending request to Infor', [$action, $request]);
    $ch_result = curl_exec($ch);
    $status = curl_getinfo($ch);

    if ( !in_array($status['http_code'], [200, 201, 202, 203, 204] )) {
      $message = 'Infor protocol error: ' . $status['http_code'] . 'Request: '  . $request;
      $this->logger->error($ch_result);
      throw new \Exception($ch_result);
    }

    $this->logger->notice('Got response from InforProtocol', [$ch_result]);
    return $this->response($ch_result, $action);
  }


  /**
   * @param $response
   * @return string
   */
  private function response($response, $action)
  {
    $this->logger->debug("Extracting data from Infor protocollo response for action " . $action);
    $this->logger->debug($response);
    $dom = new DOMDocument;
    $dom->loadXML($response);

    $esito = $dom->getElementsByTagName('esito')->item(0)->nodeValue;

    if ($esito !== 'OK') {
      $message = 'Infor protocol rsponse error: ' . $response;
      $this->logger->error($message);
      throw new \Exception($message);
    }

    if ($action === self::ACTION_ALLEGA_DOCUMENTO) {
      return $esito;
    }

    $content = '';
    $content .= $dom->getElementsByTagName('anno')->item(0)->nodeValue;
    $content .= '/' . $dom->getElementsByTagName('numero')->item(0)->nodeValue;
    return $content;
  }

  private function retrieveOggettoFromPratica(Pratica $pratica): string
  {
    if ($pratica instanceof IscrizioneRegistroAssociazioni) {
      return "STANZA DEL CITTADINO: " . $pratica->getServizio()->getName() . " " . $pratica->getNomeAssociazione();
    }

    if ($pratica instanceof OccupazioneSuoloPubblico) {
      return "STANZA DEL CITTADINO: " . $pratica->getServizio()->getName() . " " . $pratica->getNomeIniziativa();
    }

    return "STANZA DEL CITTADINO: " . $pratica->getServizio()->getName() .
      " " . $pratica->getUser()->getFullName();
  }

  private function retrieveOggettoRispostaFromPratica(Pratica $pratica): string
  {
    if ($pratica->getEsito() === Pratica::ACCEPTED) {
      return "STANZA DEL CITTADINO : rilascio  " . $pratica->getServizio()->getName() .
        " " . $pratica->getRispostaOperatore()->getOriginalFilename();
    }

    return "STANZA DEL CITTADINO : rifiuto  " . $pratica->getServizio()->getName() .
      " " . $pratica->getUser()->getFullName();
  }

  private function retrieveProtocolloParametersForEnteAndServizio(Pratica $pratica): array
  {
    $ente = $pratica->getEnte();
    $servizio = $pratica->getServizio();
    if (!empty($servizio->getProtocolloParameters())) {
      return (array)$servizio->getProtocolloParameters();
    }
    return (array)$ente->getProtocolloParametersPerServizio($servizio);
  }

  /**
   * @param $numeroDiProtocollo
   * @param AllegatoInterface $allegato
   * @param array $parameters
   * @return string
   * @throws FileNotFoundException
   */
  protected function createSendAllegatoRequestBody($numeroDiProtocollo, AllegatoInterface $allegato, array $parameters): ?string
  {
    /**
     * Protocol number has the format \d{4}/\d{2}
     */
    $numeroDiProtocollo = explode('/', $numeroDiProtocollo);
    $anno = $numeroDiProtocollo[0];
    $numero = $numeroDiProtocollo[1];

    $xml = new SimpleXMLElement('<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:web="http://webservices.jprotocollo.jente.infor.arezzo.it/"/>');
    $xml->addChild('soapenv:Header');
    $body = $xml->addChild('soapenv:Body');
    $function = $body->addChild('web:allegaDocumento', null, 'web');
    $richiesta = $function->addChild('richiestaAllegaDocumento', null, '');
    $richiesta->addChild('web:username', $parameters['infor_username'], 'web');

    $riferimento = $richiesta->addChild('web:riferimento', null, 'web');
    $riferimento->addChild('web:anno', $anno, 'web');
    $riferimento->addChild('web:numero', $numero, 'web');

    if ($this->fileService->attachmentExists($allegato)) {
      $documento = $richiesta->addChild('web:documento', null, 'web');
      $documento->addChild('web:titolo', substr($allegato->getDescription(), 0, 99), 'web');
      $documento->addChild('web:nomeFile', $allegato->getFilename(), 'web');
      $documento->addChild('web:file', base64_encode($this->fileService->getAttachmentContent($allegato)), 'web');
      return trim(str_replace(array('<?xml version="1.0"?>', ' xmlns:web="web"', ' xmlns=""'), '', $xml->asXML()));
    }
    $this->logger->critical('Missing actual allegato file for allegato, NOT sending it to Infor protocol ', [$allegato]);

    throw new \Error('Missing actual allegato file for allegato, NOT sending it to Infor protocol');
  }

  /**
   * @param SimpleXMLElement $protocollaArrivo
   * @param array $parameters
   */
  private function createClassificazioneNode(SimpleXMLElement $protocollaArrivo, array $parameters): void
  {
    $classificazione = $protocollaArrivo->addChild('web:classificazione', null, 'web');
    $classificazione->addChild('web:titolario', $parameters['classifica'], 'web');
    $fascicolo = $classificazione->addChild('web:fascicolo', null, 'web');
    $fascicolo->addChild('web:anno', explode('/', $parameters['fascicolo'])[0], 'web');
    $fascicolo->addChild('web:numero', explode('/', $parameters['fascicolo'])[1], 'web');
  }

  /**
   * @param SimpleXMLElement $protocollaArrivo
   * @param array $parameters
   */
  private function createAltriDatiNode(SimpleXMLElement $protocollaArrivo, array $parameters): void
  {
    $altriDati = $protocollaArrivo->addChild('web:altriDati', null, 'web');
    $tipoDocumento = $altriDati->addChild('web:tipoDocumento', null, 'web');
    $tipoDocumento->addChild('web:codice', $parameters['tipo_documento'], 'web');
    $tramite = $altriDati->addChild('web:tramite', null, 'web');
    $tramite->addChild('web:codice', $parameters['tramite'], 'web');
  }

  /**
   * @param SimpleXMLElement $protocollaArrivo
   * @param array $parameters
   */
  private function createSmistamentoNode(SimpleXMLElement $protocollaArrivo, array $parameters): void
  {
    $smistamenti = $protocollaArrivo->addChild('web:smistamenti', null, 'web');
    $smistamento = $smistamenti->addChild('web:smistamento', null, 'web');
    $corrispondente = $smistamento->addChild('web:corrispondente', null, 'web');
    $corrispondente->addChild('web:codice', $parameters['smistamento'], 'web');
  }

  public function sendRichiestaIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
  {
    $this->sendAllegatoToProtocollo($pratica, $allegato);
  }

  public function sendAllegatoRichiestaIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $richiestaIntegrazione, AllegatoInterface $allegato)
  {
    $this->sendAllegatoToProtocollo($pratica, $allegato);
  }

}
