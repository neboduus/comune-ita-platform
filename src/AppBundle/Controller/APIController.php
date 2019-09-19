<?php
namespace AppBundle\Controller;

use AppBundle\Entity\Ente;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\PraticaRepository;
use AppBundle\Entity\Servizio;
use AppBundle\Services\InstanceService;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use FOS\RestBundle\Controller\AbstractFOSRestController;

/**
 * Class APIController
 * @property EntityManager em
 * @property InstanceService is
 * @package AppBundle\Controller
 * @Route("/api/v1.0")
 */
class APIController extends AbstractFOSRestController
{
    const CURRENT_API_VERSION = 'v1.0';
    const SCHEDA_INFORMATIVA_REMOTE_PARAMETER = 'remote';

    public function __construct(EntityManager $em, InstanceService $is)
    {
        $this->em = $em;
        $this->is = $is;
    }

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
     * @Route("/usage",name="api_usage")
     * @return JsonResponse
     */
    public function usageAction()
    {
        $repo = $this->em->getRepository(Pratica::class);
        $pratiche = $repo->findSubmittedPraticheByEnte($this->is->getCurrentInstance());
        $serviziRepository = $this->getDoctrine()->getRepository('AppBundle:Servizio');
        $servizi = $serviziRepository->findBy(
            [
                'status' => [1]
            ]
        );

        $count = array_reduce($pratiche,function($acc, $el) {
            $year = (new \DateTime())->setTimestamp($el->getSubmissionTime())->format('Y');
            try {
                $acc[$year]++;
            } catch(\Exception $e) {
                $acc[$year] = 1;
            }

            return $acc;
        },[]);

        return new JsonResponse([
            'version'  => self::CURRENT_API_VERSION,
            'status'   => 'ok',
            'servizi'  => count($servizi),
            'pratiche' => $count

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

    /**
     * @Route("/servizioTexts/{servizio}/{ente}/{step}", name="ez_api_testi_custom_servizio_ente")
     * @ParamConverter("servizio", options={"mapping": {"servizio": "slug"}})
     * @ParamConverter("ente", options={"mapping": {"ente": "codiceMeccanografico"}})
     * @Method({"POST"})
     * @param Request  $request
     * @param Servizio $servizio
     * @param Ente     $ente
     *
     * @return Response
     */
    public function putServizioSpecificTexts(Request $request, Servizio $servizio, Ente $ente, $step)
    {
        $content = $request->getContent();
        $servizio->setCustomTextForServizioAndStep($step, $content);
        $this->getDoctrine()->getManager()->flush();
        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
