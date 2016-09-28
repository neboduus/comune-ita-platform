<?php
namespace AppBundle\Controller;

use AppBundle\Entity\Pratica;
use AppBundle\Entity\StatusChange;
use AppBundle\Logging\LogConstants;
use JMS\Serializer\Exception\UnsupportedFormatException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class APIController
 * @package AppBundle\Controller
 * @Route("/api/v1.0")
 */
class APIController extends Controller
{

    const CURRENT_API_VERSION = 'v1.0';

    /**
     * @Route("/status",name="api_status")
     * @return JsonResponse
     */
    public function statusAction()
    {
        return new JsonResponse([
            'version' => self::CURRENT_API_VERSION,
            'status' => 'ok',
        ]);
    }

    /**
     * @Route("/services",name="api_services")
     * @return JsonResponse
     */
    public function servicesAction()
    {
        $servizi = $this->getDoctrine()->getRepository('AppBundle:Servizio')->findAll();
        $out = [];
        foreach ($servizi as $servizio) {
            $out[] = [
                'name' => $servizio->getName(),
                'slug' => $servizio->getSlug(),
            ];
        }

        return new JsonResponse($out);
    }

    /**
     * @Route("/pratica/{pratica}/status", name="gpa_api_pratica_update_status")
     * @Method({"POST"})
     * @Security("has_role('ROLE_GPA')")
     * @return Response
     */
    public function addStatusChangeToPraticaAction(Request $request, Pratica $pratica)
    {
        $logger = $this->get('logger');
        $content = $request->getContent();
        if (empty($content)) {
            $logger->info(LogConstants::PRATICA_ERROR_IN_UPDATED_STATUS_FROM_GPA, [ 'statusChange' => null , 'error' => 'missing body altogether' ]);

            return new Response(null, Response::HTTP_BAD_REQUEST);
        }

        try {
            $statusChange = new StatusChange(json_decode($content, true));
        } catch (\Exception $e) {
            $logger->info(LogConstants::PRATICA_ERROR_IN_UPDATED_STATUS_FROM_GPA, [ 'statusChange' => $content , 'error' => $e ]);

            return new Response(null, Response::HTTP_BAD_REQUEST);
        }

        $pratica->setStatus($statusChange->getEvento(), $statusChange);
        $this->getDoctrine()->getManager()->flush();
        $logger->info(LogConstants::PRATICA_UPDATED_STATUS_FROM_GPA, [ 'statusChange' => $statusChange ]);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
