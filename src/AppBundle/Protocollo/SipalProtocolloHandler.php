<?php

namespace AppBundle\Protocollo;

use AppBundle\Entity\AllegatoInterface;
use AppBundle\Entity\Ente;
use AppBundle\Entity\IscrizioneRegistroAssociazioni;
use AppBundle\Entity\ModuloCompilato;
use AppBundle\Entity\OccupazioneSuoloPubblico;
use AppBundle\Entity\Pratica;
use AppBundle\Model\DefaultProtocolSettings;
use Doctrine\ORM\EntityManagerInterface;
use DOMDocument;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use SoapClient;
use SoapVar;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;
use Vich\UploaderBundle\Naming\DirectoryNamerInterface;

/**
 * Class SipalProtocolloHandler
 * @package AppBundle\Protocollo
 */
class SipalProtocolloHandler implements ProtocolloHandlerInterface
{

  const ACTION_ADD_PROTOCOLLO = 'addProtocollo';
  const ACTION_ADD_ALLEGATO = 'addAllegato';

  private $em;
  private $logger;
  private $directoryNamer;
  private $propertyMappingFactory;

  private $tempPemFile = false;
  private $tempKeyFile = false;

  public function __construct(
    EntityManagerInterface $em,
    LoggerInterface $logger,
    DirectoryNamerInterface $dn,
    PropertyMappingFactory $pmf
  ) {
    $this->directoryNamer = $dn;
    $this->em = $em;
    $this->logger = $logger;
    $this->propertyMappingFactory = $pmf;
  }

  public function getName()
  {
    return 'Sipal';
  }

  public function getConfigParameters()
  {
    return array(
      'sipal_wsUrl',
      'sipal_username',
      'sipal_token',
      'sipal_registro',
      'sipal_classificazione',
      'sipal_destinatario_interno',
    );
  }

  /**
   * @param Pratica $pratica
   *
   * @throws \Exception
   */
  public function sendPraticaToProtocollo(Pratica $pratica)
  {
    $parameters = $this->retrieveProtocolloParametersForEnteAndServizio($pratica);

    $wsUrl = $parameters['sipal_wsUrl'];
    if (strpos($wsUrl, '?wsdl') === false) {
      $wsUrl .= '?wsdl';
    }

    $user = $pratica->getUser();
    $content = "<![CDATA[<datiin>
                        <registro>".$parameters['sipal_registro']."</registro>
                        <movimentazione>A</movimentazione>
                        <tipoposta>P1</tipoposta>
                        <oggetto>" . $this->retrieveOggettoFromPratica($pratica) . "</oggetto>
                        <classificazione>".$parameters['sipal_classificazione']."</classificazione>
                        <destinatariointerno>".$parameters['sipal_destinatario_interno']."</destinatariointerno>
                        <mittenti>
                            <mittenteesterno>
                                <ragionesociale>". $user->getFullName() ."</ragionesociale>
                                <indirizzo>". $user->getIndirizzoResidenza() ."</indirizzo>
                                <comune>" . $user->getCittaResidenza() ."</comune>
                            </mittenteesterno>
                        </mittenti>
                    </datiin>]]>";

    $xml = new SimpleXMLElement('<ws:addProtocollo xmlns:ws="http://ws.fl.sipalinformatica.it/" />');
    $xml->addChild('arg0', $parameters['sipal_username'], '');
    $xml->addChild('arg1', $parameters['sipal_token'], '');
    $xml->addChild('arg2', self::ACTION_ADD_PROTOCOLLO, '');
    $xml->addChild('arg3', '[%content%]', '');
    $request = trim(str_replace(array('<?xml version="1.0"?>', ' xmlns:web="web"', ' xmlns=""'), '', $xml->asXML()));
    $request = str_replace('[%content%]', $content, $request);

    $options = array(
      'location'      => $wsUrl,
      'keep_alive'    => true,
      'trace'         => true,
      'cache_wsdl'    => WSDL_CACHE_NONE
    );

    $soapClient = new SoapClient($wsUrl, $options);
    $soapRequest = new SoapVar($request, XSD_ANYXML);
    $result = $soapClient->addProtocollo($soapRequest);

    $soap = simplexml_load_string($result->return);
    if (!$soap) {
      throw new \Exception('Errore protocollo Sipal - addAllegato Pratica: '  . $pratica->getId() . ', messaggio: la risposta soap non è un xml pasrsabile. ' . $result->return);
    }

    if ($soap->esito == '0') {
      $datiOut = $soap->datiout;
      $pratica->setNumeroProtocollo((string) $datiOut->numeroprotocollo . '/' . (string) $datiOut->anno);
    } else {
      throw new \Exception('Errore protocollo Sipal - addAllegato Pratica: '  . $pratica->getId() . ', messaggio: ' . (string) $soap->esitomsg);
    }

    /*echo  (string) $datiOut->numeroprotocollo;
    echo  (string) $datiOut->anno;
    echo  (string) $datiOut->idprotocollo;*/

  }

  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $allegato
   * @throws \Exception
   */
  public function sendAllegatoToProtocollo(Pratica $pratica, AllegatoInterface $allegato): void
  {
    $parameters = $this->retrieveProtocolloParametersForEnteAndServizio($pratica);
    $wsUrl = $parameters['sipal_wsUrl'];
    if (strpos($wsUrl, '?wsdl') === false) {
      $wsUrl .= '?wsdl';
    }

    $protocol = $pratica->getNumeroProtocollo();
    $protocolParts = explode('/', $protocol);
    if (count($protocolParts) < 1) {
      throw new \Exception('Errore protocollo Sipal - addAllegato Pratica: '  . $pratica->getId() . ', allegato:' . $allegato->getId() . ', messaggio: protocollo pratica malformato');
    }

    $attachmentType = 'N';
    if ($allegato instanceof ModuloCompilato) {
      $attachmentType = 'S';
    }

    $path = $allegato->getFile()->getPathname();
    $fileContent = base64_encode(file_get_contents($path));

    $content = "<![CDATA[<datiin>
        <registro>".$parameters['sipal_registro']."</registro>
        <numeroprotocollo>". $protocolParts[0] ."</numeroprotocollo>
        <anno>". $protocolParts[1] ."</anno>
        <oggettoallegato>".substr($allegato->getDescription(), 0, 99)."</oggettoallegato>
        <nomeallegato>".$allegato->getFilename()."</nomeallegato>
        <noteallegato>".$allegato->getDescription()."</noteallegato>
        <principale>".$attachmentType."</principale>
        <base64file>". $fileContent ."</base64file>
        </datiin>]]>";

    $xml = new SimpleXMLElement('<ws:addAllegato xmlns:ws="http://ws.fl.sipalinformatica.it/" />');
    $xml->addChild('arg0', $parameters['sipal_username'], '');
    $xml->addChild('arg1', $parameters['sipal_token'], '');
    $xml->addChild('arg2', self::ACTION_ADD_ALLEGATO, '');
    $xml->addChild('arg3', '[%content%]', '');
    $request = trim(str_replace(array('<?xml version="1.0"?>', ' xmlns:web="web"', ' xmlns=""'), '', $xml->asXML()));
    $request = str_replace('[%content%]', $content, $request);

    $options = array(
      'location'      => $wsUrl,
      'keep_alive'    => true,
      'trace'         => true,
      'cache_wsdl'    => WSDL_CACHE_NONE
    );

    $soapClient = new SoapClient($wsUrl, $options);
    $soapRequest = new SoapVar($request, XSD_ANYXML);
    $result = $soapClient->addAllegato($soapRequest);

    $soap = simplexml_load_string($result->return);
    if (!$soap) {
      throw new \Exception('Errore protocollo Sipal - addAllegato Pratica: '  . $pratica->getId() . ', allegato:' . $allegato->getId() . ', messaggio: la risposta soap non è un xml pasrsabile. ' . $result->return);
    }

    if ($soap->esito == '0') {
      $pratica->addNumeroDiProtocollo([
        'id' => $allegato->getId(),
        'protocollo' => (string) $soap->esitomsg,
      ]);
    } else {
      throw new \Exception( 'Errore protocollo Sipal - addAllegato - Pratica: '  . $pratica->getId() . ', allegato:' . $allegato->getId() . ', messaggio: ' . (string) $soap->esitomsg);
    }
  }


  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $allegato
   * @throws \Exception
   */
  public function sendRispostaIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
  {
    $this->sendAllegatoToProtocollo($pratica, $allegato);
  }


  /**
   * @param Pratica $pratica
   * @param AllegatoInterface $rispostaIntegrazione
   * @param AllegatoInterface $allegato
   * @throws \Exception
   */
  public function sendIntegrazioneToProtocollo(
    Pratica $pratica,
    AllegatoInterface $rispostaIntegrazione,
    AllegatoInterface $allegato
  ) {
    $this->sendAllegatoToProtocollo($pratica, $allegato);
  }


  /**
   * @param Pratica $pratica
   */
  public function sendRispostaToProtocollo(Pratica $pratica)
  {
    $risposta = $pratica->getRispostaOperatore();
    $this->sendAllegatoToProtocollo($pratica, $risposta);
  }

  public function sendRitiroToProtocollo(Pratica $pratica)
  {
    $withdrawAttachment = $pratica->getWithdrawAttachment();
    $this->sendAllegatoToProtocollo($pratica, $withdrawAttachment);
  }


  public function sendAllegatoRispostaToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
  {
    $this->sendAllegatoToProtocollo($pratica, $allegato);
  }

  public function sendRichiestaIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $allegato)
  {
    $this->sendAllegatoToProtocollo($pratica, $allegato);
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

  private function retrieveOggettoFromPratica(Pratica $pratica): string
  {
    return $pratica->getServizio()->getName() . " - " . $pratica->getUser()->getFullName();
  }


}
