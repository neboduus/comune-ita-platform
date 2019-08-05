<?php

namespace AppBundle\Services;

use AppBundle\Entity\Pratica;
use AppBundle\Entity\GiscomPratica;
use AppBundle\Entity\SciaPraticaEdilizia;
use AppBundle\Entity\StatusChange;
use AppBundle\Mapper\Giscom\GiscomStatusMapper;
use AppBundle\Mapper\Giscom\SciaPraticaEdilizia as PraticaEdilizia;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Class GiscomAPIAdapterService
 */
class GiscomAPIAdapterService implements GiscomAPIAdapterServiceInterface
{

    /**
     * @var $client Client
     */
    private $client;

    /**
     * @var $em EntityManagerInterface
     */
    private $em;

    /**
     * @var $logger LoggerInterface
     */
    private $logger;

    /**
     * @var GiscomAPIMapperService
     */
    private $mapper;

    /**
     * @var PraticaStatusService
     */
    private $statusService;

    /**
     * @var GiscomStatusMapper
     */
    private $giscomStatusMapper;


    /**
     * GiscomAPIAdapterService constructor.
     *
     * @param Client $client
     * @param EntityManagerInterface $em
     * @param LoggerInterface $logger
     */
    public function __construct(
        Client $client,
        EntityManagerInterface $em,
        LoggerInterface $logger,
        GiscomAPIMapperService $mapper,
        PraticaStatusService $statusService,
        GiscomStatusMapper $giscomStatusMapper
    )
    {
        $this->client = $client;
        $this->em = $em;
        $this->logger = $logger;
        $this->mapper = $mapper;
        $this->statusService = $statusService;
        $this->giscomStatusMapper = $giscomStatusMapper;
    }

    /**
     * @param Pratica|GiscomPratica $pratica
     *
     * @return ResponseInterface
     * @throws \Exception
     */
    public function sendPraticaToGiscom(GiscomPratica $pratica): ResponseInterface
    {

        if (!$pratica instanceof SciaPraticaEdilizia) {
            throw new \InvalidArgumentException("Giscom requires a " . SciaPraticaEdilizia::class . " instance");
        }

        $method = 'POST';
        $giscomPratica = $this->mapper->map($pratica);
        $logContext = $this->mapper->map($pratica, false);

        $this->logger->info("Updating (or Creating) pratica on Giscom side", $logContext);

        $request = new Request(
            $method,
            $this->client->getConfig('base_uri') . $pratica->getEnte()->getCodiceAmministrativo() . '/Pratiche',
            ['Content-Type' => 'application/json'],
            json_encode($giscomPratica)
        );

        try {
            $response = $this->client->send($request);

            $this->logger->debug('Giscom response: ', [$response->getBody()]);
            $status = $response->getStatusCode();

            if ($status == 201 || $status == 204) {
                if ($status == 204) {


                    if ($pratica->getStatus() !== Pratica::STATUS_COMPLETE && $pratica->getStatus() !== Pratica::STATUS_CANCELLED) {
                        $this->statusService->setNewStatus($pratica, Pratica::STATUS_PENDING_AFTER_INTEGRATION);
                    }
                    $this->logger->debug('Correctly updated pratica on Giscom Side', $logContext);


                } else {
                    $responseBody = json_decode($response->getBody(), true);
                    if (!isset($responseBody['Stato']['Codice'])) {
                        throw new \Exception("Error parsing Giscom response");
                    }

                    $statusCode = strtolower($responseBody['Stato']['Codice']);
                    $mappedStatus = $this->giscomStatusMapper->map($statusCode);
                    $statusChange['evento'] = $mappedStatus;
                    $statusChange['operatore'] = 'Giscom';
                    $statusChange['responsabile'] = 'Giscom';
                    $statusChange['struttura'] = 'Giscom';
                    $statusChange['timestamp'] = time();
                    $statusChange = new StatusChange($statusChange);

                    $this->statusService->setNewStatus($pratica, $mappedStatus, $statusChange);

                    $this->logger->debug('Correctly created pratica on Giscom Side', $logContext);

                    $this->askRelatedCFsForPraticaToGiscom($pratica);
                }

            } else {
                $this->logger->error("Error when sending pratica {$pratica->getId()} on Giscom Side ", $logContext);
                throw new \Exception("Error when sending pratica {$pratica->getId()} on Giscom Side");
            }

            return $response;

        } catch (\Exception $e) {
            $response = new Response(500, [], $e->getMessage());
            if (method_exists($e, 'getResponse')) {
                $response = $e->getResponse();
            }

            /**
             * Remote response body here should be  {Message: somestring}
             */
            $logContext['remote_error_response'] = $response->getBody() . "";
            if (!is_object($logContext['remote_error_response'])) {
                try {
                    $logContext['remote_error_response'] = json_decode($logContext['remote_error_response'],true);
                } catch (\Exception $e) {
                    /* NOOP: null or already  */
                }
            }
            $this->logger->error("Error when creating pratica {$pratica->getId()} on Giscom Side", $logContext);

            // La pratica non va più rifiutata ma lasciata in Stato Pending, da Giscom manualmente rifiuteranno la pratica
            //$mappedStatus = $this->giscomStatusMapper->map(GiscomStatusMapper::GISCOM_STATUS_RIFIUTATA);
            //$mappedStatus = Pratica::STATUS_CANCELLED;
            /*$statusChange = null;
            $statusChange['evento'] = $mappedStatus;
            $statusChange['operatore'] = 'Giscom';
            $statusChange['responsabile'] = 'Giscom';
            $statusChange['struttura'] = 'Giscom';
            $statusChange['timestamp'] = time();
            $statusChange['message'] = isset($logContext['remote_error_response']['Message']) ? $logContext['remote_error_response']['Message'] : null;
            $statusChange = new StatusChange($statusChange);

            $this->statusService->setNewStatus($pratica, $mappedStatus, $statusChange);*/

            return $response;
        }

    }

    /**
     * @param Pratica|GiscomPratica $pratica
     *
     * @return ResponseInterface
     * @throws \Exception
     */
    public function askRelatedCFsForPraticaToGiscom(GiscomPratica $pratica)
    {
        $logContext = ['id' => $pratica->getId()];

        $this->logger->debug('Asking related CFs for pratica on Giscom side', $logContext);
        $this->logger->info('Asking related CFs for pratica on Giscom side', $logContext);

        $request = new Request(
            'GET',
            $this->client->getConfig('base_uri') . $pratica->getEnte()->getCodiceAmministrativo() . '/Pratiche/' . $pratica->getId() . '/cfabilitati'
        );

        $response = $this->client->send($request);

        if ($response->getStatusCode() == 200) {
            $this->logger->debug('Correctly retrieve cfs from Giscom Side', $logContext);
            $this->logger->info('Correctly retrieve cfs from Giscom Side', $logContext);
        } else {
            $this->logger->error('Error when retrieving cfs from Giscom Side, no action due manual cancellation by Giscom', $logContext);
            return;
            //throw new \Exception("Error when retrieving cfs of pratica {$pratica->getId()} from Giscom Side");
        }

        $relatedCFs = json_decode($response->getBody());
        $pratica->setRelatedCFs($relatedCFs);
        $this->em->persist($pratica);
        $this->em->flush();

        return $response;
    }
}