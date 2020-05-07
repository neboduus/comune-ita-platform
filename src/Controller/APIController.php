<?php

namespace App\Controller;

use App\Entity\Ente;
use App\Entity\Pratica;
use App\Multitenancy\TenantAwareFOSRestController;
use App\Entity\Servizio;
use App\Repository\PraticaRepository;
use App\Services\InstanceService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Multitenancy\Annotations\MustHaveTenant;

/**
 * Class APIController
 * @property EntityManager em
 * @property InstanceService is
 * @package App\Controller
 * @Route("/api/v1.0")
 * @MustHaveTenant()
 */
class APIController extends TenantAwareFOSRestController
{
    const CURRENT_API_VERSION = 'v1.0';

    const SCHEDA_INFORMATIVA_REMOTE_PARAMETER = 'remote';

    public function __construct(EntityManagerInterface $em, InstanceService $is)
    {
        $this->em = $em;
        $this->is = $is;
    }

    /**
     * @Route("/status",name="api_status")
     * @return JsonResponse
     */
    public function status()
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
    public function usage()
    {
        /** @var PraticaRepository $repo */
        $repo = $this->em->getRepository(Pratica::class);
        $pratiche = $repo->findSubmittedPraticheByEnte($this->is->getCurrentInstance());
        $serviziRepository = $this->getDoctrine()->getRepository('App:Servizio');
        $servizi = $serviziRepository->findBy(
            [
                'status' => [1]
            ]
        );

        $count = array_reduce($pratiche, function ($acc, Pratica $el) {
            $year = (new \DateTime())->setTimestamp($el->getSubmissionTime())->format('Y');
            try {
                $acc[$year]++;
            } catch (\Exception $e) {
                $acc[$year] = 1;
            }

            return $acc;
        }, []);

        return new JsonResponse([
            'version' => self::CURRENT_API_VERSION,
            'status' => 'ok',
            'servizi' => count($servizi),
            'pratiche' => $count

        ]);
    }

    /**
     * @Route("/user/{pratica}/notes",name="api_set_notes_for_pratica", methods={"POST"})
     * @param Request $request
     * @param Pratica $pratica
     * @return Response
     */
    public function postNotes(Request $request, Pratica $pratica)
    {
        $user = $this->getUser();
        if ($pratica->getUser() !== $user) {
            return new Response(null, Response::HTTP_NOT_FOUND);
        }
        $newNote = $request->getContent();
        $pratica->setUserCompilationNotes($newNote);
        $this->getDoctrine()->getManager()->flush();
        return new Response();
    }

    /**
     * @Route("/user/{pratica}/notes",name="api_get_notes_for_pratica", methods={"GET"})
     * @param Pratica $pratica
     * @return Response
     */
    public function getNotes(Pratica $pratica)
    {
        $user = $this->getUser();
        if ($pratica->getUser() !== $user) {
            return new Response(null, Response::HTTP_NOT_FOUND);
        }

        return new Response($pratica->getUserCompilationNotes());
    }

    /**
     * @Route("/schedaInformativa/{servizio}/{ente}", name="ez_api_scheda_informativa_servizio_ente")
     * @ParamConverter("servizio", options={"mapping": {"servizio": "slug"}})
     * @ParamConverter("ente", options={"mapping": {"ente": "codiceMeccanografico"}})
     *
     * @param Request $request
     * @param Servizio $servizio
     * @param Ente $ente
     *
     * @return Response
     */
    public function putSchedaInformativaForServizioAndEnte(Request $request, Servizio $servizio, Ente $ente)
    {
        if (method_exists($servizio, 'setSchedaInformativaPerEnte')) {
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

        throw new \RuntimeException("Method Servizio::setSchedaInformativaPerEnte not found");
    }

    /**
     * @Route("/servizioTexts/{servizio}/{ente}/{step}", name="ez_api_testi_custom_servizio_ente", methods={"POST"})
     * @ParamConverter("servizio", options={"mapping": {"servizio": "slug"}})
     * @ParamConverter("ente", options={"mapping": {"ente": "codiceMeccanografico"}})
     * @param Request $request
     * @param Servizio $servizio
     * @param Ente $ente
     * @param $step
     * @return Response
     */
    public function putServizioSpecificTexts(Request $request, Servizio $servizio, Ente $ente, $step)
    {
        if (method_exists($servizio, 'setCustomTextForServizioAndStep')) {
            $content = $request->getContent();
            $servizio->setCustomTextForServizioAndStep($step, $content, $ente);
            $this->getDoctrine()->getManager()->flush();

            return new Response(null, Response::HTTP_NO_CONTENT);
        }

        throw new \RuntimeException("Method Servizio::setCustomTextForServizioAndStep not found");
    }
}
