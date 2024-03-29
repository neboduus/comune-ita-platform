<?php

namespace App\Controller\Rest;

use Nelmio\ApiDocBundle\Annotation\Security;

use App\Entity\Pratica;
use App\Services\InstanceService;
use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use OpenApi\Annotations as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * Class StatusAPIController
 * @property EntityManager em
 * @property InstanceService is
 * @package App\Controller
 * @Route("/status")
 */
class StatusAPIController extends AbstractFOSRestController
{


  /**
   * Retrieve an Application status
   * @Rest\Get("/applications/{id}", name="status_application_api_get")
   *
   * @OA\Response(
   *    response=200,
   *    description="Application has been accepted",
   *    @OA\JsonContent(
   *       type="object",
   *       @OA\Property(property="result", type="boolean"),
   *       @OA\Property(property="status", type="integer")
   *    )
   * )
   *
   * @OA\Response(
   *    response=404,
   *    description="Not found application"
   * )
   *
   * @OA\Response(
   *    response=406,
   *    description="Application status is rejected or pending",
   *    @OA\JsonContent(
   *       type="object",
   *       @OA\Property(property="result", type="boolean", default="false"),
   *       @OA\Property(property="status", type="integer")
   *    )
   *
   * )
   * @OA\Tag(name="status")
   *
   * @param $id
   * @return \FOS\RestBundle\View\View
   */
  public function getApplicationStatusAction($id)
  {
    try {
      $repository = $this->getDoctrine()->getRepository('App\Entity\Pratica');
      /** @var Pratica $result */
      $result = $repository->find($id);
      if ($result === null) {
        return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
      }

      if ($result->getStatus() == Pratica::STATUS_COMPLETE && $result->getEsito()) {
        return $this->view(['result' => true, 'status' => $result->getStatus()], Response::HTTP_OK);
      } else {
        return $this->view(['result' => false, 'status' => $result->getStatus()], Response::HTTP_NOT_ACCEPTABLE);
      }


    } catch (\Exception $e) {
      return $this->view(["Error"], Response::HTTP_NOT_FOUND);
    }
  }
}
