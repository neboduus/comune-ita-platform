<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Pratica;
use AppBundle\Entity\RichiestaIntegrazioneDTO;
use AppBundle\Entity\SciaPraticaEdilizia;
use AppBundle\Entity\StatusChange;
use AppBundle\Logging\LogConstants;
use AppBundle\Mapper\Giscom\GiscomStatusMapper;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class APIController
 * @package AppBundle\Controller
 * @Route("/api/v1.0")
 */
class GiscomAPIController extends Controller
{

    const CURRENT_API_VERSION = 'v1.0';

    /**
     * @var \Symfony\Bridge\Monolog\Logger
     */
    private $logger;

    /**
     * @var \AppBundle\Services\PraticaStatusService
     */
    private $statusService;

    /**
     * @var GiscomStatusMapper
     */
    private $statusMapper;

    /**
     * @var \AppBundle\Services\PraticaIntegrationService
     */
    private $integrationService;

    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->logger = $this->container->get('logger');
        $this->statusService = $this->container->get('ocsdc.pratica_status_service');
        $this->statusMapper = $this->container->get('ocsdc.status_mapper.giscom');
        $this->integrationService = $this->container->get('ocsdc.pratica_integration_service');
    }

    /**
     * @Route("/giscom/pratica/{pratica}/status", name="giscom_api_pratica_update_status")
     * @Method({"POST"})
     * @Security("has_role('ROLE_GISCOM')")
     * @return Response
     */
    public function addStatusChangeToPraticaAction(Request $request, Pratica $pratica)
    {
        $content = $request->getContent();
        if (empty($content)) {
            $this->logger->info(LogConstants::PRATICA_ERROR_IN_UPDATED_STATUS_FROM_GISCOM, ['statusChange' => null, 'error' => 'missing body altogether']);

            return new Response(null, Response::HTTP_BAD_REQUEST);
        }

        try {
            $statusChange = $this->statusMapper->getStatusChangeFromRequest($request);
            $this->statusService->setNewStatus($pratica, $statusChange->getEvento(), $statusChange);
        } catch (\Exception $e) {
            $this->logger->info(LogConstants::PRATICA_ERROR_IN_UPDATED_STATUS_FROM_GISCOM, ['statusChange' => $content, 'error' => $e]);
            return new Response(null, Response::HTTP_BAD_REQUEST);
        }

        $this->logger->info(LogConstants::PRATICA_UPDATED_STATUS_FROM_GISCOM, ['statusChange' => $statusChange]);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/giscom/pratica/{pratica}/protocolli", name="giscom_api_pratica_update_protocolli")
     * @Method({"POST"})
     * @Security("has_role('ROLE_GISCOM')")
     * @return Response
     */
    public function addProtocolliToPraticaAction(Request $request, Pratica $pratica)
    {
        $content = $request->getContent();
        if (empty($content)) {
            $this->logger->info(LogConstants::PRATICA_ERROR_IN_UPDATED_PROTOCOLLI_FROM_GISCOM, ['statusChange' => null, 'error' => 'missing body altogether']);

            return new Response(null, Response::HTTP_BAD_REQUEST);
        }
        $protocolli = json_decode($content);
        foreach ($protocolli as $protocollo) {
            $pratica->addNumeroDiProtocollo($protocollo);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($pratica);
        $em->flush();

        $this->logger->info(LogConstants::PRATICA_UPDATED_PROTOCOLLO_FROM_GISCOM, ['protocolli' => $protocolli]);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/giscom/pratica/{pratica}/richiestaIntegrazioni", name="giscom_api_pratica_richiesta_integrazioni")
     * @Method({"POST"})
     * @Security("has_role('ROLE_GISCOM')")
     * @return Response
     */
    public function createIntegrationRequestAction(Request $request, SciaPraticaEdilizia $pratica)
    {
        $payload = json_decode($request->getContent(), true);
        $message = isset($payload['Nota']) ? $payload['Nota'] : '';

        $this->get('logger')->info(LogConstants::RICHIESTA_INTEGRAZIONE_FROM_GISCOM, [
            'id'=> $pratica->getId(),
            'request' => $payload
        ]);

        $richiestaIntegrazione = new RichiestaIntegrazioneDTO($payload, null, $message);

        $statusChange = new StatusChange([
            'evento' => $this->statusMapper->map(GiscomStatusMapper::GISCOM_STATUS_RICHIESTA_INTEGRAZIONI),
            'responsabile' => 'Giscom',
            'operatore' => 'Giscom',
            'struttura' => 'Giscom',
            'timestamp' => time(),
        ]);

        $this->integrationService->requestIntegration($pratica, $richiestaIntegrazione);

        return new Response(null, 201);
    }
}