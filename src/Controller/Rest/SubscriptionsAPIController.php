<?php


namespace App\Controller\Rest;


use App\Services\InstanceService;
use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swagger\Annotations as SWG;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * Class SubscriptionsAPIController
 * @property EntityManager em
 * @property InstanceService is
 * @package AppBundle\Controller
 * @Route("/subscriptions")
 *
 */
class SubscriptionsAPIController extends AbstractFOSRestController
{

  /**
   * Check wether the subscription is a valid one
   * @Rest\Get("/availability", name="validity_subscription_api_get")
   *
   * @SWG\Response(
   *    response=200,
   *    description="Subscription is valid",
   *    @SWG\Schema(
   *       type="object",
   *       @SWG\Property(property="result", type="boolean")
   *    )
   * )
   *
   * @SWG\Response(
   *    response=400,
   *    description="Invalid request"
   * )
   *
   * @SWG\Response(
   *    response=406,
   *    description="Duplicate subscription",
   *    @SWG\Schema(
   *       type="object",
   *       @SWG\Property(property="result", type="boolean")
   *    )
   *
   * )
   * @SWG\Tag(name="subscriptions")
   * @return View

   *
   * @param Request $request
   */
  public function getSubscriptionAvailabilityAction(Request $request)
  {
    $fiscalCode = $request->query->get('cf');
    $code = $request->query->get('code');

    if (!$fiscalCode || !$code ) {
      return $this->view("Missing parameter code or cf", Response::HTTP_BAD_REQUEST);
    }

    $subscription = $this->getDoctrine()->getManager()->createQueryBuilder()
      ->select('subscription')
      ->from('AppBundle:Subscription', 'subscription')
      ->leftJoin('subscription.subscription_service', 'service')
      ->leftJoin('subscription.subscriber', 'subscriber')
      ->where('service.code = :code')
      ->andWhere('subscriber.fiscal_code = :fiscal_code')
      ->setParameter('code', $code)
      ->setParameter('fiscal_code', $fiscalCode)
      ->getQuery()
      ->getResult();

    if (count($subscription) == 0) {
      return $this->view(['result' => true], Response::HTTP_OK);
    } else {
      return $this->view(['result' => false], Response::HTTP_NOT_ACCEPTABLE);
    }
  }

}
