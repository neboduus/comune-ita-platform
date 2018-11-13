<?php

namespace AppBundle\Protocollo;

use AppBundle\Entity\AllegatoInterface;
use AppBundle\Entity\IscrizioneRegistroAssociazioni;
use AppBundle\Entity\OccupazioneSuoloPubblico;
use AppBundle\Entity\Pratica;
use Doctrine\ORM\EntityManagerInterface;
use DOMDocument;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;
use Vich\UploaderBundle\Naming\DirectoryNamerInterface;

/**
 * Class InforProtocolloHandler
 * @package AppBundle\Protocollo
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

    const ACTION_ALLEGA_DOCUMENTO = 'allegaDocumento';
    const ACTION_INSERISCI_ARRIVO = 'inserisciArrivo';
    const ACTION_INSERISCI_PARTENZA = 'inserisciPartenza';

    private $em;
    private $logger;
    private $directoryNamer;
    private $propertyMappingFactory;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger, DirectoryNamerInterface $dn, PropertyMappingFactory $pmf)
    {
        $this->directoryNamer = $dn;
        $this->em = $em;
        $this->logger = $logger;
        $this->propertyMappingFactory = $pmf;
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


        /**
         * <web:classificazione>
        <web:titolario>11.01</web:titolario>
        <web:fascicolo>
        <web:anno>2016</web:anno>
        <web:numero>2</web:numero>
        </web:fascicolo>
        </web:classificazione>
         */
        $classificazione = $protocollaArrivo->addChild('web:classificazione', null, 'web');
        $titolario = $classificazione->addChild('web:titolario', $parameters['arrivo']['classifica'], 'web');
        $fascicolo = $classificazione->addChild('web:fascicolo', null, 'web');
        $anno = $fascicolo->addChild('web:anno', explode('/', $parameters['arrivo']['fascicolo'])[0], 'web');
        $numero = $fascicolo->addChild('web:numero', explode('/', $parameters['arrivo']['fascicolo'])[1], 'web');


        /**
            <!--Optional:-->
           <web:altriDati>
            <!--Optional:-->
            <web:tipoDocumento>
             <web:codice>CV</web:codice>
            </web:tipoDocumento>
            <web:tramite>
             <web:codice>WEB</web:codice>
            </web:tramite>
            <!--Optional:-->
            <web:visibilita>5</web:visibilita>
           </web:altriDati>
         */
        $altriDati = $protocollaArrivo->addChild('web:altriDati', null, 'web');
        $tipoDocumento = $altriDati->addChild('web:tipoDocumento', null, 'web');
        $tipoDocumento_codice = $tipoDocumento->addChild('web:codice', $parameters['arrivo']['tipo_documento'], 'web');
        $tramite = $altriDati->addChild('web:tramite', null, 'web');
        $tramite_codice = $tramite->addChild('web:codice', $parameters['arrivo']['tramite'], 'web');

        $soggetti = $protocollaArrivo->addChild('web:soggetti', null, 'web');
        $soggetto = $soggetti->addChild('web:soggetto', null, 'web');
        $soggetto->addChild('web:denominazione', $pratica->getRichiedenteCognome(). ' '. $pratica->getRichiedenteNome() .' '. $pratica->getRichiedenteCodiceFiscale(), 'web');

        if($pratica->getRichiedenteEmail()) {
            $soggetto->addChild('web:indirizzo', $pratica->getRichiedenteEmail(), 'web');
        }

        $smistamenti = $protocollaArrivo->addChild('web:smistamenti', null, 'web');
        $smistamento = $smistamenti->addChild('web:smistamento', null, 'web');
        $corrispondente = $smistamento->addChild('web:corrispondente', null, 'web');
        $corrispondente->addChild('web:codice', 'STC', 'web');

        $protocollaArrivo->addChild('web:oggetto', $this->retrieveOggettoFromPratica($pratica), 'web');

        $request = trim(str_replace(array('<?xml version="1.0"?>', ' xmlns:web="web"', ' xmlns=""'), '', $xml->asXML()));
        $response = $this->sendRequest(self::ACTION_INSERISCI_ARRIVO, $request, $parameters['infor_wsUrl']);

        $pratica->setNumeroProtocollo($response);
    }

    /**
     * @param Pratica $pratica
     * @param AllegatoInterface $allegato
     */
    public function sendAllegatoToProtocollo(Pratica $pratica, AllegatoInterface $allegato): void
    {
        $parameters = $this->retrieveProtocolloParametersForEnteAndServizio($pratica);

        $request = $this->createSendAllegatoRequestBody($pratica, $allegato, $parameters);

        $response = $this->sendRequest(self::ACTION_ALLEGA_DOCUMENTO, $request, $parameters['infor_wsUrl']);
        $pratica->addNumeroDiProtocollo([
            'id' => $allegato->getId(),
            'protocollo' => $response,
        ]);
    }

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
        $soggetto->addChild('web:denominazione', 'Stanza del Cittadino', 'web');
        $soggetto->addChild('web:indirizzo', 'no-reply@stanzadelcittadino.it', 'web');

//        $soggetti->addChild('web:altriSoggetti', '', 'web');
        $protocollaPartenza->addChild('web:oggetto', $this->retrieveOggettoRispostaFromPratica($pratica), 'web');
        $request = trim(str_replace(array('<?xml version="1.0"?>', ' xmlns:web="web"', ' xmlns=""'), '', $xml->asXML()));
        $response = $this->sendRequest(self::ACTION_INSERISCI_PARTENZA , $request, $parameters['infor_wsUrl']);
        $risposta->setNumeroProtocollo($response);
        $this->em->persist($risposta);
        $this->em->flush();
    }

    public function sendAllegatoRispostaToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
    {
        $this->sendAllegatoToProtocollo($pratica, $allegato);
    }

    private function sendRequest($action, $request, $url)
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
        $this->logger->notice('Sending request to Infor', [$action, $request]);

        $ch_result = curl_exec($ch);
        $this->logger->notice('Got response from InforProtocol', [$ch_result]);
        return $this->response($ch_result, $action);
    }


    /**
     * @param $response
     * @return string
     */
    private function response($response, $action)
    {
        $this->logger->debug("Extracting data from Infor protocollo response for action ".$action);
        $this->logger->debug($response);
        $dom = new DOMDocument;
        $dom->loadXML($response);

        $esito = $dom->getElementsByTagName('esito')->item(0)->nodeValue;

        if($esito === 'KO') {
            $message = 'Infor protocol refused to protocol pratica: '.$response;
            throw new \Exception($message);
        }

        if($action === self::ACTION_ALLEGA_DOCUMENTO) {
            return $esito;
        }

        $content = '';
        $content .= $dom->getElementsByTagName('anno')->item(0)->nodeValue;
        $content .= '/' . $dom->getElementsByTagName('numero')->item(0)->nodeValue;
        return $content;
    }

    private function retrieveOggettoFromPratica(Pratica $pratica): string
    {
        if($pratica instanceof IscrizioneRegistroAssociazioni){
            return "STANZA DEL CITTADINO: ".$pratica->getServizio()->getName()." ". $pratica->getNomeAssociazione();
        }

        if($pratica instanceof OccupazioneSuoloPubblico){
            return "STANZA DEL CITTADINO: ".$pratica->getServizio()->getName()." ". $pratica->getNomeIniziativa();
        }

        return "STANZA DEL CITTADINO: ".$pratica->getServizio()->getName().
            " ". $pratica->getRichiedenteCognome() .
            " " .$pratica->getRichiedenteNome();
    }

    private function retrieveOggettoRispostaFromPratica(Pratica $pratica): string
    {
        if($pratica->getEsito() === Pratica::ACCEPTED) {
            return "STANZA DEL CITTADINO : rifiuto  ".$pratica->getServizio()->getName().
                " ". $pratica->getRichiedenteCognome() .
                " " .$pratica->getRichiedenteNome();
        }

        return "STANZA DEL CITTADINO : rilascio  ".$pratica->getServizio()->getName().
            " " . $pratica->getRispostaOperatore()->getOriginalFilename();
    }

    private function retrieveProtocolloParametersForEnteAndServizio(Pratica $pratica): array
    {
        $ente       = $pratica->getEnte();
        $servizio   = $pratica->getServizio();
        return (array)$ente->getProtocolloParametersPerServizio($servizio);
    }

    /**
     * @param Pratica $pratica
     * @param AllegatoInterface $allegato
     * @param array $parameters
     * @return string
     */
    protected function createSendAllegatoRequestBody(Pratica $pratica, AllegatoInterface $allegato, array $parameters): ?string
    {
        /**
         * Protocol number has the format \d{4}/\d{2}
         */
        $numeroDiProtocollo = explode('/', $pratica->getNumeroProtocollo());
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


        $mapping = $this->propertyMappingFactory->fromObject($allegato)[0];
        $destDir = $mapping->getUploadDestination() . '/' . $this->directoryNamer->directoryName($allegato, $mapping);
        $documentPath = $destDir . DIRECTORY_SEPARATOR . $allegato->getFilename();

        if ($documentPath && file_exists($documentPath)) {
            $documento = $richiesta->addChild('web:documento', null, 'web');
            $documento->addChild('web:titolo', $allegato->getDescription(), 'web');
            $documento->addChild('web:nomeFile', $allegato->getOriginalFilename(), 'web');
            $documento->addChild('web:file', base64_encode(file_get_contents($documentPath)), 'web');
            return trim(str_replace(array('<?xml version="1.0"?>', ' xmlns:web="web"', ' xmlns=""'), '', $xml->asXML()));
        }
            $this->logger->critical('Missing actual allegato file for allegato, NOT sending it to Infor protocol ', [$allegato]);

        throw new \Error('Missing actual allegato file for allegato, NOT sending it to Infor protocol');
    }
}
