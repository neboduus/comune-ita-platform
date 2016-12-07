<?php

namespace AppBundle\Protocollo;

use AppBundle\Entity\AllegatoInterface;
use AppBundle\Entity\Pratica;
use AppBundle\Protocollo\Exception\ResponseErrorException;
use GuzzleHttp\Client;
use AppBundle\Entity\ModuloCompilato;

class PiTreProtocolloHandler implements ProtocolloHandlerInterface
{
    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
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

    private function getParameters(Pratica $pratica, AllegatoInterface $allegato = null)
    {
        $ente = $pratica->getEnte();
        $servizio = $pratica->getServizio();
        $parameters = (array)$ente->getProtocolloParametersPerServizio($servizio);
        $parameters = new PiTreProtocolloParameters($parameters);

        if ($allegato instanceof AllegatoInterface){
            $parameters->setDocumentId($pratica->getIdDocumentoProtocollo());
            $parameters->setFilePath($allegato->getFile()->getPathname());
            $parameters->setAttachmentDescription($allegato->getDescription());
        }else{
            /** @var ModuloCompilato $moduloCompilato */
            $moduloCompilato = $pratica->getModuliCompilati()->first();
            $parameters->setProjectDescription($pratica->getServizio()->getName() . ' ' . $pratica->getUser()->getFullName());
            $parameters->setDocumentDescription($moduloCompilato->getDescription());
            $parameters->setDocumentObj($pratica->getServizio()->getName());
            $parameters->setFilePath($moduloCompilato->getFile()->getPathname());
        }

        return $parameters;
    }

}

