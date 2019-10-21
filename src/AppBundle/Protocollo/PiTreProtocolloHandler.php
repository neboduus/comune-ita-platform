<?php

namespace AppBundle\Protocollo;

use AppBundle\Entity\AllegatoInterface;
use AppBundle\Entity\CPSUser;
use AppBundle\Entity\ModuloCompilato;
use AppBundle\Entity\Pratica;
use AppBundle\Protocollo\Exception\ResponseErrorException;
use GuzzleHttp\Client;

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
        // trasmissionIDArray va valorizzato solo per il metodo createDocumentAndAddInProject
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
     *
     * @throws ResponseErrorException
     */
    public function sendRichiestaIntegrazioneToProtocollo(Pratica $pratica, AllegatoInterface $richiesta)
    {
        $parameters = $this->getRichiestaIntegrazioneParameters($pratica, $richiesta);
        $parameters->set('method', 'createDocumentAndAddInProject');
        $queryString = http_build_query($parameters->all());

        $response = $this->client->get('?' . $queryString);
        $responseData = new PiTreResponseData((array)json_decode((string)$response->getBody(), true));

        if ($responseData->getStatus() == 'success') {
            $richiesta->setNumeroProtocollo($responseData->getNProt());
            $richiesta->setIdDocumentoProtocollo($responseData->getIdDoc());
        } else {
            throw new ResponseErrorException($responseData . ' on query ' . $queryString);
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
        /** @var CPSUser $user */
        $user = $pratica->getUser();

        $parameters = (array)$ente->getProtocolloParametersPerServizio($servizio);
        $parameters = new PiTreProtocolloParameters($parameters);



        if(!$parameters->getInstance()) {
            $parameters->setInstance($this->instance);
        }

        if ($allegato instanceof AllegatoInterface) {
            $parameters->setDocumentId($pratica->getIdDocumentoProtocollo());
            $parameters->setFilePath($allegato->getFile()->getPathname());
            $parameters->setAttachmentDescription($allegato->getDescription() . ' ' . $user->getFullName(). ' ' . $user->getCodiceFiscale());
        } else {

            $object =  $pratica->getServizio()->getName() . ' ' . $user->getFullName() . ' ' . $user->getCodiceFiscale();
            if ( $pratica->getOggetto() != null && !empty($pratica->getOggetto())) {
                $object = $pratica->getOggetto() . ' - ' . $user->getFullName() . ' ' . $user->getCodiceFiscale();
            }

            /** @var ModuloCompilato $moduloCompilato */
            $moduloCompilato = $pratica->getModuliCompilati()->first();
            $parameters->setProjectDescription($pratica->getServizio()->getName() . ' ' . $user->getFullName() . ' ' . $user->getCodiceFiscale());

            $parameters->setDocumentObj($object);
            $parameters->setDocumentDescription($moduloCompilato->getDescription());

            $parameters->setFilePath($moduloCompilato->getFile()->getPathname());
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
        $risposta   = $pratica->getRispostaOperatore();
        $ente       = $pratica->getEnte();
        $servizio   = $pratica->getServizio();
        /** @var CPSUser $user */
        $user = $pratica->getUser();

        $parameters = (array)$ente->getProtocolloParametersPerServizio($servizio);
        $parameters = new PiTreProtocolloParameters($parameters);

        if(!$parameters->getInstance()) {
            $parameters->setInstance($this->instance);
        }

        if ($allegato instanceof AllegatoInterface) {
            $parameters->setDocumentId($risposta->getIdDocumentoProtocollo());
            $parameters->setFilePath($allegato->getFile()->getPathname());
            $parameters->setAttachmentDescription($allegato->getDescription() . ' ' . $user->getFullName() . ' ' . $user->getCodiceFiscale());
        } else {

            $object =  $pratica->getServizio()->getName() . ' ' . $user->getFullName() . ' ' . $user->getCodiceFiscale();
            if ( $pratica->getOggetto() != null && !empty($pratica->getOggetto())) {
                $object = $pratica->getOggetto() . ' - ' . $user->getFullName() . ' ' . $user->getCodiceFiscale();
            }

            $parameters->setDocumentObj('Risposta ' . $object);
            $parameters->setDocumentDescription('Risposta ' . $pratica->getServizio()->getName() . ' ' . $user->getFullName() . ' ' . $user->getCodiceFiscale());

            $parameters->setFilePath($risposta->getFile()->getPathname());
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

    private function getRichiestaIntegrazioneParameters(Pratica $pratica, AllegatoInterface $richiesta)
    {
        $ente       = $pratica->getEnte();
        $servizio   = $pratica->getServizio();
        /** @var CPSUser $user */
        $user = $pratica->getUser();

        $parameters = (array)$ente->getProtocolloParametersPerServizio($servizio);
        $parameters = new PiTreProtocolloParameters($parameters);

        if(!$parameters->getInstance()) {
            $parameters->setInstance($this->instance);
        }

        $object =  $pratica->getServizio()->getName() . ' ' . $user->getFullName() . ' ' . $user->getCodiceFiscale();
        if ( $pratica->getOggetto() != null && !empty($pratica->getOggetto())) {
            $object = $pratica->getOggetto() . ' - ' . $user->getFullName() . ' ' . $user->getCodiceFiscale();
        }

        $parameters->setDocumentObj('Richiesta integrazione ' . $object);
        $parameters->setDocumentDescription('Richiesta integrazione '  . $pratica->getServizio()->getName() . ' ' . $user->getFullName() . ' ' . $user->getCodiceFiscale());

        $parameters->setFilePath($richiesta->getFile()->getPathname());
        $parameters->setIdProject($pratica->getNumeroFascicolo());
        $parameters->setCreateProject(false);
        $parameters->setDocumentType("P");

        $parameters->setSenderName($user->getNome());
        $parameters->setSenderSurname($user->getCognome());
        $parameters->setSenderCf($user->getCodiceFiscale());
        $parameters->setSenderEmail($user->getEmail());

        return $parameters;
    }

}

