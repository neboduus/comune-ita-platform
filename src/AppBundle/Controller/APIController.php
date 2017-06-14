<?php
namespace AppBundle\Controller;

use AppBundle\Entity\Ente;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class APIController
 * @package AppBundle\Controller
 * @Route("/api/v1.0")
 */
class APIController extends Controller
{

    const CURRENT_API_VERSION = 'v1.0';
    const SCHEDA_INFORMATIVA_REMOTE_PARAMETER = 'remote';

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
     * @Route("/user/{pratica}/notes",name="api_set_notes_for_pratica")
     * @Method({"POST"})
     * @param Request $request
     * @param Pratica $pratica
     * @return Response
     */
    public function postNotesAction(Request $request, Pratica $pratica)
    {
        $user = $this->getUser();
        if($pratica->getUser() !== $user){
            return new Response(null, Response::HTTP_NOT_FOUND);
        }
        $newNote = $request->getContent();
        $pratica->setUserCompilationNotes($newNote);
        $this->getDoctrine()->getManager()->flush();
        return new Response();
    }

    /**
     * @Route("/user/{pratica}/notes",name="api_get_notes_for_pratica")
     * @Method({"GET"})
     * @param Request $request
     * @param Pratica $pratica
     * @return Response
     */
    public function getNotesAction(Request $request, Pratica $pratica)
    {
        $user = $this->getUser();
        if($pratica->getUser() !== $user){
            return new Response(null, Response::HTTP_NOT_FOUND);
        }

        return new Response($pratica->getUserCompilationNotes());
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
     * @Route("/schedaInformativa/{servizio}/{ente}", name="ez_api_scheda_informativa_servizio_ente")
     * @ParamConverter("servizio", options={"mapping": {"servizio": "slug"}})
     * @ParamConverter("ente", options={"mapping": {"ente": "codiceMeccanografico"}})
     *
     * @param Request  $request
     * @param Servizio $servizio
     * @param Ente     $ente
     *
     * @return Response
     */
    public function putSchedaInformativaForServizioAndEnteAction(Request $request, Servizio $servizio, Ente $ente)
    {
        if (!$request->query->has(self::SCHEDA_INFORMATIVA_REMOTE_PARAMETER)) {
            return new Response(null, Response::HTTP_BAD_REQUEST);
        }

        $schedaInformativa = json_decode(file_get_contents($request->query->get(self::SCHEDA_INFORMATIVA_REMOTE_PARAMETER)), true);

        if (!array_key_exists('data', $schedaInformativa) || !array_key_exists('metadata', $schedaInformativa)) {
            return new Response(null, Response::HTTP_BAD_REQUEST);
        }

        $servizio->setSchedaInformativaPerEnte($schedaInformativa, $ente);
        $this->getDoctrine()->getManager()->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
