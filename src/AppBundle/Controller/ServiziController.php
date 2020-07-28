<?php

namespace AppBundle\Controller;


use AppBundle\Entity\Ente;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\ServizioRepository;
use AppBundle\Handlers\Servizio\ForbiddenAccessException;
use AppBundle\Handlers\Servizio\ServizioHandlerRegistry;
use AppBundle\Logging\LogConstants;
use AppBundle\Model\FlowStep;
use AppBundle\Model\FormIOFlowStep;
use Doctrine\ORM\EntityRepository;
use Gedmo\Loggable\Entity\LogEntry;
use Gedmo\Loggable\Entity\Repository\LogEntryRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Router;


/**
 * Class ServiziController
 * @package AppBundle\Controller
 * @Route("/servizi")
 */
class ServiziController extends Controller
{
  /**
   * @Route("/", name="servizi_list")
   * @Template()
   * @param Request $request
   * @return array
   */
  public function serviziAction(Request $request)
  {
    /** @var ServizioRepository $serviziRepository */
    $serviziRepository = $this->getDoctrine()->getRepository('AppBundle:Servizio');
    $stickyServices = $serviziRepository->findStickyAvailable();
    $servizi = $serviziRepository->findNotStickyAvailable();

    return [
      'sticky_services' => $stickyServices,
      'servizi' => $servizi,
      'user' => $this->getUser(),
    ];
  }

  /**
   * @Route("/miller/{topic}/{subtopic}", name="servizi_miller", defaults={"topic":false, "subtopic":false})
   * @param string $topic
   * @param string $subtopic
   * @param Request $request
   * @return Response|array
   */
  public function serviziMillerAction($topic, $subtopic, Request $request)
  {
    return new Response(null, Response::HTTP_GONE);
  }

  /**
   * @Route("/miller_ajax/{topic}/{subtopic}", name="servizi_miller_ajax", defaults={"subtopic":false})
   * @param string $topic
   * @param string $subtopic
   * @param Request $request
   * @return Response|array
   */
  public function serviziMillerAjaxAction($topic, $subtopic, Request $request)
  {
    return new Response(null, Response::HTTP_GONE);
  }

  /**
   * @Route("/{slug}", name="servizi_show")
   * @Template()
   * @param string $slug
   * @param Request $request
   *
   * @return array
   */
  public function serviziDetailAction($slug, Request $request)
  {
    $user = $this->getUser();

    /** @var EntityRepository $serviziRepository */
    $serviziRepository = $this->getDoctrine()->getRepository('AppBundle:Servizio');

    /** @var Servizio $servizio */
    $servizio = $serviziRepository->findOneBySlug($slug);
    if (!$servizio instanceof Servizio) {
      throw new NotFoundHttpException("Servizio $slug not found");
    }

    $serviziArea = $serviziRepository->createQueryBuilder('servizio')
      ->andWhere('servizio.id != :servizio')
      ->setParameter('servizio', $servizio->getId())
      ->andWhere('servizio.ente IN (:ente)')
      ->setParameter('ente', $servizio->getEnte())
      ->andWhere('servizio.status = :status')
      ->setParameter('status', Servizio::STATUS_AVAILABLE)
      ->andWhere('servizio.topics in (:topics)')
      ->setParameter('topics', $servizio->getTopics())
      ->orderBy('servizio.name', 'asc')
      ->setMaxResults(5)
      ->getQuery()->execute();

    $handler = $this->get(ServizioHandlerRegistry::class)->getByName($servizio->getHandler());
    $ente = $this->getDoctrine()
      ->getRepository('AppBundle:Ente')
      ->findOneBy(
        [
          'slug' => $this->container->hasParameter('prefix') ? $this->container->getParameter(
            'prefix'
          ) : $request->query->get(PraticheController::ENTE_SLUG_QUERY_PARAMETER, null),
        ]
      );

    if (!$ente instanceof Ente) {
      $this->get('logger')->info(
        LogConstants::PRATICA_WRONG_ENTE_REQUESTED,
        ['headers' => $request->headers]
      );

      throw new \InvalidArgumentException(LogConstants::PRATICA_WRONG_ENTE_REQUESTED);
    }

    $canAccess = true;
    $denyAccessMessage = false;
    try {
      $handler->canAccess($servizio, $ente);
    } catch (ForbiddenAccessException $e) {
      $canAccess = false;
      $denyAccessMessage = $this->get('translator')->trans($e->getMessage(), $e->getParameters());
    }

    return [
      'user' => $user,
      'servizio' => $servizio,
      'servizi_area' => $serviziArea,
      'handler' => $handler,
      'can_access' => $canAccess,
      'deny_access_message' => $denyAccessMessage,
    ];
  }

  /**
   * @Route("/formio/{servizioId}/{servizioVersion}/{displayMode}/{formId}", name="servizi_form")
   * @param Request $request
   * @param $servizioId
   * @param $servizioVersion
   * @param $displayMode
   * @param $formId
   * @return JsonResponse
   */
  public function serviziFormAction(Request $request, $servizioId, $servizioVersion, $displayMode, $formId)
  {
    $request->setRequestFormat('json');

    $response = new JsonResponse();
    $response->setPublic();

    $cache = new FilesystemAdapter();
    $formData = $cache->getItem("formio-{$servizioId}-{$servizioVersion}-{$displayMode}-{$formId}");

    if ($formData->isHit()) {

      return $response
        ->setStatusCode(304)
        ->setJson($formData->get());

    }else{
      /** @var LogEntryRepository $repo */
      $repo = $this->getDoctrine()->getRepository(LogEntry::class);
      /** @var Servizio $servizio */
      $servizio = $this->getDoctrine()->getManager()->find(Servizio::class, $servizioId);

      $repo->revert($servizio, $servizioVersion);

      foreach ($servizio->getFlowSteps() as $flowStep) {
        $step = FlowStep::fromArray($flowStep);
        if ($step->getType() == FormIOFlowStep::TYPE && $step->hasParameter('formio_data')) {
          $sources = $step->getParameter('formio_data')['sources'];

          if (isset($sources[$formId])) {
            $json = json_encode($sources[$formId]);
            if ($displayMode === 'printable') {
              $json = str_replace('"display":"wizard"', '"display":"form"', $json);
            }

            $formData->set($json);
            $cache->save($formData);

            return $response
              ->setStatusCode(200)
              ->setJson($json);
          }

        }
      }
    }

    return $response->setStatusCode(404);
  }

  /**
   * @Route("/formio/{servizioId}/{servizioVersion}/{displayMode}/{formId}/submission", name="servizi_form_submission")
   * @param Request $request
   * @param $servizioId
   * @param $servizioVersion
   * @param $displayMode
   * @param $formId
   * @return JsonResponse
   */
  public function serviziFormSubmissionAction(Request $request, $servizioId, $servizioVersion, $displayMode, $formId)
  {
    return JsonResponse::create();
  }
}
