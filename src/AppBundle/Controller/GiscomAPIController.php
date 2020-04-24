<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Allegato;
use AppBundle\Entity\Ente;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\RichiestaIntegrazioneDTO;
use AppBundle\Entity\RispostaOperatoreDTO;
use AppBundle\Entity\SciaPraticaEdilizia;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\StatusChange;
use AppBundle\Logging\LogConstants;
use AppBundle\Mapper\Giscom\GiscomStatusMapper;
use AppBundle\Services\GiscomAPIMapperService;
use AppBundle\Services\PraticaStatusService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Ramsey\Uuid\Uuid;
use AppBundle\Mapper\Giscom\SciaPraticaEdilizia as MappedPraticaEdilizia;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\VarDumper\VarDumper;

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
     * @Route("/giscom/", name="giscom_api_ping")
     * @Method({"GET"})
     * @return Response
     */
    public function indexAction(Request $request)
    {
        return new Response('1', 200);
    }

    /**
     * @Route("/giscom/pratica/{pratica}/view", name="giscom_api_pratica_view")
     * @Method({"GET"})
     * @Security("has_role('ROLE_GISCOM')")
     * @return Response
     */
    public function viePraticaAction(Request $request, Pratica $pratica)
    {
        $mapper = $this->container->get('ocsdc.giscom_api.mapper');
        $giscomPratica = $mapper->map($pratica);

        return new JsonResponse([
            'pratica' => $giscomPratica,
        ]);

    }

    /**
     * @Route("/giscom/pratica/attachment/{attachment}", name="giscom_api_attachment")
     * @Method({"GET"})
     * @Security("has_role('ROLE_GISCOM')")
     * @return Response
     */
    public function attachmentAction(Request $request, Allegato $attachment)
    {
      $fileContent = file_get_contents($attachment->getFile()->getPathname());
      // Provide a name for your file with extension
      $filename = mb_convert_encoding($attachment->getOriginalFilename(), "ASCII", "auto");
      // Return a response with a specific content
      $response = new Response($fileContent);
      // Create the disposition of the file
      $disposition = $response->headers->makeDisposition(
        ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        $filename
      );
      // Set the content disposition
      $response->headers->set('Content-Disposition', $disposition);
      // Dispatch request
      return $response;
    }

    /**
     * @Route("/giscom/pratica/offline/create", name="giscom_api_offline_pratica_create")
     * @Method({"POST"})
     * @Security("has_role('ROLE_GISCOM')")
     * @return Response
     */
    public function createOfflinePraticaAction( Request $request )
    {

        $content = $request->getContent();
        if (empty($content)) {
            $this->logger->error(LogConstants::PRATICA_ERROR_IN_CREATE_FROM_GISCOM, [ 'payload' =>  $content, 'error' => 'missing body' ]);
            return new Response(null, Response::HTTP_BAD_REQUEST);
        }

        try {

            $content = $request->getContent();
            $data = json_decode($content, true);

            $securityUser = $this->getUser();
            $user = $this->getDoctrine()
                ->getRepository('AppBundle:OperatoreUser')
                ->findOneByUsername($securityUser->getUsername());


            $pratica = new SciaPraticaEdilizia();

            /** @var Servizio $servizio */
            $servizio = $this->getDoctrine()
                ->getRepository('AppBundle:Servizio')
                ->findOneByPraticaFCQN(SciaPraticaEdilizia::class);

            $enteSlug = $ente = null;
            if ($this->getParameter('prefix') != null) {
                $enteSlug = $this->getParameter('prefix');
            }

            if ($enteSlug != null) {
                /** @var Ente $ente */
                $ente = $this->getDoctrine()
                    ->getRepository('AppBundle:Ente')
                    ->findOneBySlug($enteSlug);
            }

            $pratica
                ->setUser($user)
                ->setEnte($ente)
                ->setServizio($servizio)
                ->setStatus(Pratica::STATUS_PENDING);

            $erogatori = $servizio->getErogatori();
            foreach ($erogatori as $erogatore) {
                if ($erogatore->getEnti()->contains($ente)) {
                    $pratica->setErogatore($erogatore);
                    break;
                }
            }
            $id = Uuid::fromString($data['id']);
            $pratica->setId($id);
            $pratica->setNumeroProtocollo( $data['protocolloPrincipale'] );
            if ( isset($data['numeroDiDocumento']))
            {
                $pratica->setIdDocumentoProtocollo( $data['numeroDiDocumento'] );
            }
            if ( isset($data['numeroDiFascicolo']))
            {
                $pratica->setNumeroFascicolo($data['numeroDiFascicolo']);
            }

            // Assegno la pratica all'operatore giscom (per impedire la presa in carico da parte di altri operatori)
            $pratica->setOperatore( $user );

            $mappedPratica = new MappedPraticaEdilizia($data);
            $mappedPratica->setId($id);
            $pratica->setDematerializedForms($mappedPratica->toHash());

            $em = $this->getDoctrine()->getManager();
            $em->persist($pratica);
            $em->flush();


            // Richiesta codici fiscali relazionati
            $giscomAdpterService = $this->get( 'ocsdc.giscom_api.adapter_delayed' );
            $giscomAdpterService->askRelatedCFsForPraticaToGiscom( $pratica );

        }catch (UniqueConstraintViolationException $e){
            $this->logger->error(LogConstants::PRATICA_ERROR_IN_CREATE_FROM_GISCOM, [ 'payload' =>  $content, 'error' => $e ]);
            return new Response('Pratica already exists', Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            $this->logger->error(LogConstants::PRATICA_ERROR_IN_CREATE_FROM_GISCOM, [ 'payload' =>  $content, 'error' => $e ]);
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $this->logger->info(LogConstants::PRATICA_CREATED_FROM_GISCOM, ['type' => $pratica]);

        return new Response(null, Response::HTTP_CREATED);
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
            $this->logger->error(LogConstants::PRATICA_ERROR_IN_UPDATED_STATUS_FROM_GISCOM, ['statusChange' => null, 'error' => 'missing body altogether']);
            return new Response(null, Response::HTTP_BAD_REQUEST);
        }

        //$this->logger->info("LOG STATUS CHANGE", ['Request content' => $content]);

        try {
            $statusChange = $this->statusMapper->getStatusChangeFromRequest($request);
            $this->statusService->setNewStatus($pratica, $statusChange->getEvento(), $statusChange);

            if ($statusChange->getEvento() == Pratica::STATUS_CANCELLED_WAITALLEGATIOPERATORE || $statusChange->getEvento() == Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE) {

                $payload = json_decode($request->getContent(), true);
                if ( (!isset($payload['FileRichiesta']) || empty($payload['FileRichiesta'])) && (!isset($payload['NoteRichiesta']) || empty($payload['NoteRichiesta'])) ) {
                    throw new \Exception('If new status is "accettazione" or "rifiuto" one of fields "FileRichiesta" or "NoteRichiesta" is mandatory');
                }

                // Approvo o rifiuto la pratica ed inserisco anche la motivazione di esito se presente
                if ($statusChange->getEvento() == Pratica::STATUS_COMPLETE_WAITALLEGATIOPERATORE) {
                    $pratica->setEsito(true);
                    $pratica->setMotivazioneEsito((isset($payload['NoteRichiesta']) && !empty($payload['NoteRichiesta']) ? $payload['NoteRichiesta'] : ' - ') );
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($pratica);
                    $em->flush();
                } else {
                    $pratica->setEsito(false);
                    $pratica->setMotivazioneEsito((isset($payload['NoteRichiesta']) && !empty($payload['NoteRichiesta']) ? $payload['NoteRichiesta'] : ' - ') );
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($pratica);
                    $em->flush();
                }

                $rispostaOperatore = new RispostaOperatoreDTO($payload, null, null);
                $this->integrationService->createRispostaOperatore($pratica, $rispostaOperatore);


                /*$file = (isset($payload['FileRichiesta']) && !empty($payload['FileRichiesta'])) ? $payload['FileRichiesta'] : false;
                if ($file) {
                    $rispostaOperatore = new RispostaOperatoreDTO($payload, null, null);
                    $this->integrationService->createRispostaOperatore($pratica, $rispostaOperatore);
                }*/

            }


        } catch (\Exception $e) {
            $this->logger->error(LogConstants::PRATICA_ERROR_IN_UPDATED_STATUS_FROM_GISCOM, ['statusChange' => $content, 'error' => $e->getMessage()]);
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
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


        // Check FileRichiesta
        /*$file = (isset($payload['FileRichiesta']) && !empty($payload['FileRichiesta'])) ? $payload['FileRichiesta'] : false;
        if (!$file) {
            return new Response(null, Response::HTTP_BAD_REQUEST);
        }*/
        if ( (!isset($payload['FileRichiesta']) || empty($payload['FileRichiesta'])) && (!isset($payload['NoteRichiesta']) || empty($payload['NoteRichiesta'])) ) {
            return new Response('Fields "FileRichiesta" or "NoteRichiesta" are mandatory', Response::HTTP_BAD_REQUEST);
        }

        $mappedPratica = new MappedPraticaEdilizia($pratica->getDematerializedForms());
        $allowedProperties = $mappedPratica->getAllowedProperties();

        // Check if integration request is correct for current paperwork
        $integrationsKeys = array('elencoAllegatiAllaDomanda', 'elencoAllegatiTecnici', 'elencoProvvedimenti');
        foreach ($integrationsKeys as $key) {
            if (!empty($payload[$key])) {
                foreach ($payload[$key] as $request) {
                    if (!in_array($request, $allowedProperties)) {
                        return new Response('Key not allowed fot pratica: ' . $mappedPratica->getTipo(), Response::HTTP_BAD_REQUEST);
                    }
                }
            }
        }

        $message = isset($payload['NoteRichiesta']) ? $payload['NoteRichiesta'] : '';

        $this->get('logger')->info(LogConstants::RICHIESTA_INTEGRAZIONE_FROM_GISCOM, [
            'id'=> $pratica->getId(),
            'request' => $payload
        ]);

        $richiestaIntegrazione = new RichiestaIntegrazioneDTO($payload, null, $message);

        /*$statusChange = new StatusChange([
            'evento' => $this->statusMapper->map(GiscomStatusMapper::GISCOM_STATUS_RICHIESTA_INTEGRAZIONI),
            'responsabile' => 'Giscom',
            'operatore' => 'Giscom',
            'struttura' => 'Giscom',
            'timestamp' => time(),
        ]);*/

        $this->integrationService->requestIntegration($pratica, $richiestaIntegrazione);

        return new Response(null, 201);
    }

}
