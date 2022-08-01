<?php

namespace App\Controller\General;

use App\InstancesProvider;
use Artprima\PrometheusMetricsBundle\Metrics\Renderer;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class MetricsController extends Controller
{

  /**
   * @var Renderer
   */
  private $metricsRenderer;

  /**
   * MetricsController constructor.
   * @param Renderer $metricsRenderer
   */
  public function __construct(Renderer $metricsRenderer)
  {
    $this->metricsRenderer = $metricsRenderer;
  }

  /**
   * @Route("/metrics", name="sdc_metrics")
   *
   * @return Response
   */
  public function metricsAction()
  {
    return $this->metricsRenderer->renderResponse();
  }

  /**
   * @Route("/prometheus.json", name="prometheus")
   */
  public function prometheusAction(Request $request)
  {
    $result = [];
    $hostname = $request->getHost();
    $env = null;

    $scheme = $request->isSecure() ? 'https' : 'http';

    foreach (InstancesProvider::factory()->getInstances() as $identifier => $instance){
      $indentifierParts = explode('/', $identifier);
      $result[] = [
        "targets" => [$hostname],
        "labels" => [
          "job" => $hostname,
          "env" => $env,
          "__scheme__" => $scheme,
          "__metrics_path__" => "/". $indentifierParts[1] ."/metrics",
        ],
      ];
    }
    $request->setRequestFormat('json');

    return new JsonResponse(json_encode($result), 200, [], true);
  }
}
