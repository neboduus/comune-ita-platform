<?php

namespace App\Controller\Rest;

use App\Entity\CPSUser;
use App\Model\Payment;
use App\Entity\OperatoreUser;
use App\Entity\Pratica;
use App\Entity\Servizio;
use App\Security\Voters\ApplicationVoter;
use App\Services\PaymentService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class PaymentsAPIController
 * @package App\Controller
 * @Route("/payments")
 */
class PaymentsAPIController extends AbstractFOSRestController
{
  const CURRENT_API_VERSION = '1.0';

  /**
   * @var EntityManagerInterface
   */
  private $entityManager;

  /**
   * @var PaymentService
   */
  private $paymentService;

  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * @param EntityManagerInterface $entityManager
   * @param PaymentService $paymentService
   * @param LoggerInterface $logger
   */
  public function __construct(
    EntityManagerInterface $entityManager,
    PaymentService $paymentService,
    LoggerInterface $logger
  ) {
    $this->entityManager = $entityManager;
    $this->logger = $logger;
    $this->paymentService = $paymentService;
  }

  /**
   * List all payments
   * @Rest\Get("", name="payments_api_list")
   *
   *
   * @SWG\Parameter(
   *      name="remote_id",
   *      in="query",
   *      type="string",
   *      required=false,
   *      description="Uuid of remote object of payments"
   *  )
   *
   *
   * @SWG\Response(
   *     response=200,
   *     description="Retrieve list of services",
   *     @SWG\Schema(
   *         type="array",
   *         @SWG\Items(ref=@Model(type=Payment::class, groups={"read"}))
   *     )
   * )
   *
   * @SWG\Tag(name="payments")
   * @param Request $request
   * @return View
   */
  public function getPaymentsAction(Request $request)
  {
    $this->denyAccessUnlessGranted(['ROLE_CPS_USER', 'ROLE_OPERATORE', 'ROLE_ADMIN']);

    $remoteId = $request->get('remote_id', false);

    if ($remoteId) {
      $repository = $this->entityManager->getRepository('App\Entity\Pratica');
      /** @var Pratica $result */
      $result = $repository->find($remoteId);
      if ($result === null) {
        return $this->view(["Application not found"], Response::HTTP_NOT_FOUND);
      }
      $this->denyAccessUnlessGranted(ApplicationVoter::VIEW, $result);
    }

    try {

      $data = $this->paymentService->getPayments($remoteId);

      if (empty($data)) {
        return $this->view(["Payment data not found"], Response::HTTP_NOT_FOUND);
      }

      return $this->view($data, Response::HTTP_OK);
    } catch (\Exception $exception) {
      return $this->view($exception->getMessage(), Response::HTTP_BAD_REQUEST);
    }
  }

}