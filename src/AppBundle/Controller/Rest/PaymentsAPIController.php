<?php

namespace AppBundle\Controller\Rest;

use AppBundle\Entity\CPSUser;
use AppBundle\Model\Payment;
use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Security\Voters\ApplicationVoter;
use AppBundle\Services\PaymentService;
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
 * @package AppBundle\Controller
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
   * @Rest\Get("", name="payments_list")
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
      $repository = $this->entityManager->getRepository('AppBundle:Pratica');
      /** @var Pratica $result */
      $result = $repository->find($remoteId);
      if ($result === null) {
        return $this->view(["Application not found"], Response::HTTP_NOT_FOUND);
      }
      $this->denyAccessUnlessGranted(ApplicationVoter::VIEW, $result);

    }

    try {

      $payment = [
        'id' => '30807c20-c9f5-11ec-9852-3f4ee6491b02',
        'user_id' => 'bc984fa2-71b5-4f9e-af6c-c914675677a7',
        'type' => 'PAGOPA',
        'tenant_id' => '2eeb8374-c8f9-4260-9e94-22504e262eb1',
        'service_id' => 'df171f10-bea7-4701-afd5-797693fd3b08',
        'created_at' => '2022-08-17T14:53:58+02:00',
        'updated_at' => '2022-08-17T14:53:58+02:00',
        'status' => 'CREATION_PENDING',
        'reason' => 'PagamentoTari-3c819c1d-6587-448d-8ba4-3b6f23e87ed4',
        'remote_id' => $remoteId,
        'payment' => [
          'transaction_id' => null,
          'paid_at' => null,
          'expire_at' => '2022-08-17T14:53:58+02:00',
          'amount' => '10.00',
          'currency' => 'EUR',
          'notice_code' => null,
          'iud' => '3f:4e:e6:49:1b:02',
          'iuv' => null,
          'split' =>
            [
              0 => [
                'code' => '2020/1',
                'amount' => '8',
                'meta' => [],
              ],
              1 => [
                'code' => '2020/2',
                'amount' => '2',
                'meta' => [],
              ],
            ],
        ],
        'links' =>
          [
            'online_payment_begin' => [
              'url' => 'https://efil-proxy.boat.opencontent.io/online-payment/2def7663-5b9f-4b95-847f-2b618d486e53',
              'last_opened_at' => '2022-05-19T14:54:22+02:00',
              'method' => 'GET',
            ],
            'online_payment_landing' => [
              'url' => 'https://devsdc.opencontent.it/comune-di-bugliano/it/pratiche/31dafc6a-95ba-4665-af2c-0f26227b8dc3/payment-callback',
              'last_opened_at' => null,
              'method' => 'GET',
            ],
            'offline_payment' => [
              'url' => 'https://efil-proxy.boat.opencontent.io/notice/2def7663-5b9f-4b95-847f-2b618d486e53',
              'last_opened_at' => null,
              'method' => 'GET',
            ],
            'receipt' => [
              'url' => 'https://efil-proxy.boat.opencontent.io/receipt/2def7663-5b9f-4b95-847f-2b618d486e53',
              'last_opened_at' => null,
              'method' => 'GET',
            ],
            'notify' => [
              0 => [
                'url' => 'https://www2.stanzadelcittadino.it/comune-di-bugliano/api/applications/3c819c1d-6587-448d-8ba4-3b6f23e87ed4/payment',
                'method' => 'POST',
                'sent_at' => null,
              ],
            ],
            'update' => [
              'url' => 'https://efil-proxy.boat.opencontent.io/update/2def7663-5b9f-4b95-847f-2b618d486e53',
              'method' => 'GET',
              'last_check_at' => '2022-05-19T14:59:29+02:00',
              'next_check_at' => null,
            ],
          ],
        'payer' => [
          'type' => 'human',
          'tax_identification_number' => 'CLNVTR76P01G822Q',
          'name' => 'Vittorino',
          'family_name' => 'Coliandro',
          'street_name' => 'ViaGramsci',
          'building_number' => '1',
          'postal_code' => '56056',
          'town_name' => 'Bugliano',
          'country_subdivision' => 'PI',
          'country' => 'IT',
          'email' => 'info@comune.bugliano.pi.it',
        ],
        'event_id' => '2dbb08a0-c9f7-11ec-9852-3f4ee6491b02',
        'event_version' => '1.0',
        'event_created_at' => '2022-08-17T14:53:58+02:00',
        'app_id' => 'topic-init1.0.0',
      ];


      return $this->view([$payment], Response::HTTP_OK);
    } catch (\Exception $exception) {
      return $this->view($exception->getMessage(), Response::HTTP_BAD_REQUEST);
    }
  }

}
