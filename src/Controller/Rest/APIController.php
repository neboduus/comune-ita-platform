<?php

namespace App\Controller\Rest;

use App\Entity\Ente;
use App\Entity\Pratica;
use App\Entity\PraticaRepository;
use App\Entity\Servizio;
use App\Services\InstanceService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use FOS\RestBundle\Controller\AbstractFOSRestController;

/**
 * Class APIController
 * @property EntityManager em
 * @property InstanceService is
 * @package App\Controller
 * @Route("/v1.0")
 */
class APIController extends AbstractFOSRestController
{
  const CURRENT_API_VERSION = 'v1.0';
  const SCHEDA_INFORMATIVA_REMOTE_PARAMETER = 'remote';

  /** @var EntityManagerInterface */
  private $em;

  /** @var InstanceService */
  private $is;

  public function __construct(EntityManagerInterface $em, InstanceService $is)
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
    $serviziRepository = $this->getDoctrine()->getRepository('App\Entity\Servizio');
    $servizi = $serviziRepository->findBy(
      [
        'status' => [1],
      ]
    );

    $count = array_reduce($pratiche, function ($acc, $el) {
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
      'pratiche' => $count,

    ]);
  }

  /**
   * @Route("/user/{pratica}/notes",name="api_set_notes_for_pratica", methods={"POST"})
   * @param Request $request
   * @param Pratica $pratica
   * @return Response
   */
  public function postNotesAction(Request $request, Pratica $pratica)
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
   * @param Request $request
   * @param Pratica $pratica
   * @return Response
   */
  public function getNotesAction(Request $request, Pratica $pratica)
  {
    $user = $this->getUser();
    if ($pratica->getUser() !== $user) {
      return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }

    return new Response($pratica->getUserCompilationNotes());
  }

}
