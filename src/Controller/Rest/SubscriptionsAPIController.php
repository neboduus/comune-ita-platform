<?php


namespace App\Controller\Rest;


use App\BackOffice\SubcriptionsBackOffice;
use App\Entity\CPSUser;
use App\Entity\Subscription;
use App\Entity\SubscriptionService;
use App\Security\Voters\BackofficeVoter;
use App\Security\Voters\SubscriptionVoter;
use App\Services\InstanceService;
use App\Utils\FormUtils;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;


/**
 * Class SubscriptionsAPIController
 * @property EntityManager em
 * @property InstanceService is
 * @package App\Controller
 * @Route("/subscriptions")
 *
 */
class SubscriptionsAPIController extends AbstractApiController
{
  const SUPPORTED_API_VERSIONS = array(2);

  /** @var EntityManagerInterface */
  private $em;

  /**
   * @var LoggerInterface
   */
  private $logger;

  public function __construct(EntityManagerInterface $em, LoggerInterface $logger, $defaultApiVersion)
  {
    parent::__construct($defaultApiVersion, self::SUPPORTED_API_VERSIONS);
    $this->em = $em;
    $this->logger = $logger;
  }

  /**
   * Check wether the subscription is a valid one
   * @Rest\Get("/availability", name="validity_subscription_api_get")
   *
   * @OA\Response(
   *    response=200,
   *    description="Subscription is valid",
   *    @OA\JsonContent(
   *       type="object",
   *       @OA\Property(property="result", type="boolean")
   *    )
   * )
   *
   * @OA\Response(
   *    response=400,
   *    description="Invalid request"
   * )
   *
   * @OA\Response(
   *    response=406,
   *    description="Duplicate subscription",
   *    @OA\JsonContent(
   *       type="object",
   *       @OA\Property(property="result", type="boolean")
   *    )
   *
   * )
   * @OA\Tag(name="subscriptions")
   * @param Request $request
   * @return View
   *
   */
  public function getSubscriptionAvailabilityAction(Request $request)
  {
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      SubcriptionsBackOffice::PATH,
      SubcriptionsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );

    $fiscalCode = $request->query->get('cf');
    $code = $request->query->get('code');

    if (!$fiscalCode || !$code) {
      return $this->view(["Missing parameter code or cf"], Response::HTTP_BAD_REQUEST);
    }

    $subscription = $this-$this->em->createQueryBuilder()
      ->select('subscription')
      ->from('App:Subscription', 'subscription')
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

  /**
   * Retrieve all Subscriptions
   * @Rest\Get("", name="subscriptions_api_get_v2")
   *
   * @Security(name="Bearer")
   *
   * @OA\Parameter(
   *      name="version",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Version of Api, default 1. Version 1 is not available"
   *  )
   *
   * @OA\Parameter(
   *      name="subscriptionService",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Subscription service id"
   *  )
   *
   * @OA\Parameter(
   *      name="code",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Subscription service code"
   *  )
   *
   * @OA\Parameter(
   *      name="tags",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="List of comma-separated subscription service tags"
   *  )
   *
   * @OA\Parameter(
   *      name="subscriptionsAvailable",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Filter by suscriptions availability"
   *  )
   *
   * @OA\Parameter(
   *      name="subscriptionServiceStatus",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Subscription service status (available statuses are 0-pending, 1-active, 2-unactive)"
   *  )
   *
   * @OA\Parameter(
   *      name="fiscalCode",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Subscriber fiscal code"
   *  )
   *
   * @OA\Parameter(
   *      name="status",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Subscription status, (available statuses are 'active' or 'withdraw')"
   *  )
   *
   * @OA\Response(
   *     response=200,
   *     description="Retrieve all subscriptions",
   *     @OA\JsonContent(
   *     		type="array",
   *          	@OA\items(
   *          		type="object",
   *              	@OA\Property(property="id", type="string", description="Subscription identifier", format="uuid"),
   *              	@OA\Property(property="subscriber_name", type="string", description="Subscriber name"),
   *                @OA\Property(property="subscriber_surname", type="string", description="Subscriber surname"),
   *                @OA\Property(property="subscriber_fiscal_code", type="string", description="Subscriber fiscale code"),
   *                @OA\Property(property="related_cfs", type="array", description="Subscription related cfs", @OA\items(type="string")),
   *                @OA\Property(property="subscription_service_name", type="string", description="Subscription service name"),
   *                @OA\Property(property="subscription_service_code", type="string", description="Subscription service code"),
   *                @OA\Property(property="subscription_service_id", type="string", description="Subscription service identifier", format="uuid"),
   *                @OA\Property(property="status", type="string", description="Subscription status (active or withdraw)", enum={"active", "withdraw"}),
   *                @OA\Property(property="created_at", type="string", format="date-time", description="Subscription creation datetime")
   *          	),
   *      )
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Tag(name="subscriptions")
   *
   * @return View
   */
  public function getSubscriptionsAction(Request $request): View
  {
    $this->checkRequestedVersion($request);
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      SubcriptionsBackOffice::PATH,
      SubcriptionsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );

    $user = $this->getUser();

    $subscriptionServiceParameter = $request->get('subscriptionService', false);
    $codeParameter = $request->get('code', false);
    $tagsParameter = $request->get('tags', false);
    $subscriptionsAvailable = $request->get('subscriptionsAvailable', false);
    $fiscalCodeParameter = $request->get('fiscalCode', false);
    $subscriptionServiceStatusParameter = $request->get('subscriptionServiceStatus', SubscriptionService::STATUS_ACTIVE);
    $subscriptionStatusParameter = $request->get('status', false);

    $qb = $this->em->createQueryBuilder()
      ->select(
        'subscription.id as id',
        'subscriber.name as subscriber_name',
        'subscriber.surname as subscriber_surname',
        'subscriber.fiscal_code as subscriber_fiscal_code',
        'subscription.relatedCFs as related_cfs',
        'service.name as subscription_service_name',
        'service.id as subscription_service_id',
        'service.code as subscription_service_code',
        'subscription.status as status',
        'subscription.created_at as created_at'
      )
      ->from(Subscription::class, 'subscription')
      ->join('subscription.subscriber', 'subscriber')
      ->join('subscription.subscription_service', 'service')
      ->where('service.status = :status')
      ->setParameter('status', $subscriptionServiceStatusParameter);


    if ($subscriptionServiceParameter) {
      $qb->andWhere('service.id =:subscriptionServiceId')
        ->setParameter('subscriptionServiceId', $subscriptionServiceParameter);
    }

    if ($codeParameter) {
      $qb->andWhere('service.code = :code')
        ->setParameter('code', $codeParameter);
    }

    if ($subscriptionsAvailable && strtolower($subscriptionsAvailable) == 'true') {
      $qb->andWhere('service.subscriptionEnd >= :today AND service.subscriptionBegin <= :today')
        ->setParameter('today', new \DateTime());
    }
    if ($fiscalCodeParameter) {
      $qb->andWhere('LOWER(subscriber.fiscal_code) = LOWER(:fiscalCode)')
        ->setParameter('fiscalCode', $fiscalCodeParameter);
    }

    if ($tagsParameter) {
      foreach (explode(',', $tagsParameter) as $tag)
        $qb->andWhere("LOWER(service.tags) LIKE LOWER('%" . $tag . "%')");
    }

    if ($subscriptionStatusParameter) {
      $qb->andWhere('subscription.status = LOWER(:subscriptionStatus)')
        ->setParameter('subscriptionStatus', $subscriptionStatusParameter);
    }

    // Filter by user permissions
    if ($user instanceof CPSUser) {
      $relatedSubscriptions = [];
      $sql = 'SELECT id from subscription as s where (LOWER(related_cfs::text))::jsonb @> \'"' . strtolower($user->getCodiceFiscale()) . '"\'';

      try {
        $stmt = $this->em->getConnection()->prepare($sql);
        $relatedSubscriptions = $stmt->executeQuery()->fetchFirstColumn();
      } catch (Exception | \Doctrine\DBAL\Exception $e) {
        $this->logger->error('Unable to find related subscriptions for user ' . $user->getCodiceFiscale() . ': ' . $e->getMessage(), ['request' => $request]);
      }

      $qb->andWhere('LOWER(:user) = LOWER(subscriber.fiscal_code) OR subscription.id IN (:related_subscriptions)')
        ->setParameter('user', $user->getCodiceFiscale())
        ->setParameter('related_subscriptions', $relatedSubscriptions);
    }
    try {
      $subscriptions = $qb->getQuery()->getResult();
    } catch (\Exception $e) {
      $data = [
        'type' => 'error',
        'title' => 'There was an error during save process',
        'description' => 'Contact technical support at support@opencontent.it'
      ];
      $this->logger->error($e->getMessage(), ['request' => $request]);
      return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
    }
    return $this->view($subscriptions, Response::HTTP_OK);
  }

  /**
   * Retrieve a Subscription
   * @Rest\Get("/{id}", name="subscription_api_get_v2")
   *
   * @OA\Response(
   *     response=200,
   *     description="Retrieve a Subscription",
   *     @OA\JsonContent(
   *     		type="object",
   *        @OA\Property(property="id", type="string", description="Subscription identifier", format="uuid"),
   *        @OA\Property(property="subscriber_name", type="string", description="Subscriber name"),
   *        @OA\Property(property="subscriber_surname", type="string", description="Subscriber surname"),
   *        @OA\Property(property="subscriber_fiscal_code", type="string", description="Subscriber fiscale code"),
   *        @OA\Property(property="related_cfs", type="array", description="Subscription related cfs", @OA\items(type="string")),
   *        @OA\Property(property="subscription_service_name", type="string", description="Subscription service name"),
   *        @OA\Property(property="subscription_service_code", type="string", description="Subscription service code"),
   *        @OA\Property(property="subscription_service_id", type="string", description="Subscription service identifier", format="uuid"),
   *        @OA\Property(property="status", type="string", description="Subscription status (active or withdraw)", enum={"active", "withdraw"}),
   *        @OA\Property(property="created_at", type="string", format="date-time", description="Subscription creation datetime")
   *    ),
   * )
   *
   * @Security(name="Bearer")
   *
   * @OA\Parameter(
   *      name="version",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Version of Api, default 1. Version 1 is not available"
   *  )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Subscription not found"
   * )
   * @OA\Tag(name="subscriptions")
   *
   * @param Request $request
   * @param $id
   *
   * @return View
   */
  public function getSubscriptionAction(Request $request, $id): View
  {
    $this->checkRequestedVersion($request);
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      SubcriptionsBackOffice::PATH,
      SubcriptionsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );

    try {
      $repository = $this->em->getRepository('App\Entity\Subscription');
      $subscription = $repository->find($id);
    } catch (\Exception $e) {
      $this->logger->error($e->getMessage(), ['request' => $request]);
      return $this->view(["Identifier conversion error"], Response::HTTP_BAD_REQUEST);
    }
    if ($subscription === null) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }
    $this->denyAccessUnlessGranted(SubscriptionVoter::VIEW, $subscription);
    $data = [
      "id" => $subscription->getId(),
      "subscriber_name" => $subscription->getSubscriber()->getName(),
      "subscriber_surname" => $subscription->getSubscriber()->getSurname(),
      "subscriber_fiscal_code" => $subscription->getSubscriber()->getFiscalCode(),
      "related_cfs" => $subscription->getRelatedCfs(),
      "subscription_service_name" => $subscription->getSubscriptionService()->getName(),
      "subscription_service_code" => $subscription->getSubscriptionService()->getCode(),
      "subscription_service_id" => $subscription->getSubscriptionService()->getId(),
      "status" => $subscription->getStatus(),
      "created_at" => $subscription->getCreatedAt()
    ];
    return $this->view($data, Response::HTTP_OK);
  }

  /**
   * Delete a Subscription
   * @Rest\Delete("/{id}", name="subscription_api_delete_v2")
   *
   * @Security(name="Bearer")
   *
   * @OA\Parameter(
   *      name="version",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Version of Api, default 1. Version 1 is not available"
   *  )
   *
   * @OA\Response(
   *     response=204,
   *     description="The resource was deleted successfully."
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Not found"
   * )
   *
   * @OA\Tag(name="subscriptions")
   *
   * @param $id
   * @param Request $request
   *
   * @Method("DELETE")
   * @return View
   */
  public function deleteSubscriptionAction(Request $request, $id): View
  {
    $this->checkRequestedVersion($request);
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      SubcriptionsBackOffice::PATH,
      SubcriptionsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );
    $this->denyAccessUnlessGranted(['ROLE_OPERATORE', 'ROLE_ADMIN']);

    $repository = $this->em->getRepository('App\Entity\Subscription');
    $subscription = $repository->find($id);
    if ($subscription) {
      // debated point: should we 404 on an unknown nickname?
      // or should we just return a nice 204 in all cases?
      // we're doing the latter

      try {
        $this->em->remove($subscription);
        $this->em->flush();
      } catch (ForeignKeyConstraintViolationException $e) {
        $data = [
          'type' => 'error',
          'title' => 'Related Payments',
          'description' => 'This subscription has related payments'
        ];
        $this->logger->error(
          $e->getMessage(),
          ['request' => $request]
        );
        return $this->view($data, Response::HTTP_BAD_REQUEST);
      } catch (\Exception $e) {
        $data = [
          'type' => 'error',
          'title' => 'There was an error during save process',
          'description' => 'Contact technical support at support@opencontent.it'
        ];
        $this->logger->error(
          $e->getMessage(),
          ['request' => $request]
        );
        return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
      }
    } else {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }
    return $this->view(null, Response::HTTP_NO_CONTENT);
  }

  /**
   * Create a Subscription
   * @Rest\Post("", name="subscription_api_post_v2")
   *
   * @Security(name="Bearer")
   *
   * @OA\Parameter(
   *      name="version",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Version of Api, default 1. Version 1 is not available"
   *  )
   *
   * @OA\RequestBody(
   *     description="The Subscription to create",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             ref=@Model(type=Subscription::class, groups={"write"})
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=201,
   *     description="Create a Subscription"
   * )
   *
   * @OA\Response(
   *     response=400,
   *     description="Bad request"
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Tag(name="subscriptions")
   *
   * @param Request $request
   *
   * @return View
   */
  public function postSubscriptionAction(Request $request): View
  {
    $this->checkRequestedVersion($request);
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      SubcriptionsBackOffice::PATH,
      SubcriptionsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );
    $this->denyAccessUnlessGranted(['ROLE_OPERATORE', 'ROLE_ADMIN']);

    $subscription = new Subscription();
    $form = $this->createForm('App\Form\SubscriptionType', $subscription);
    $this->processForm($request, $form);

    if ($form->isSubmitted() && !$form->isValid()) {
      $errors = FormUtils::getErrorsFromForm($form);
      $data = [
        'type' => 'validation_error',
        'title' => 'There was a validation error',
        'errors' => $errors
      ];
      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    try {
      $this->em->persist($subscription);
      $this->em->flush();
    }
    catch (UniqueConstraintViolationException $e) {
      $data = [
        'type' => 'error',
        'title' => 'Duplicated subscription',
        'description' => 'This subscription already exists'
      ];
      $this->logger->error(
        $e->getMessage(),
        ['request' => $request]
      );
      return $this->view($data, Response::HTTP_BAD_REQUEST);
    } catch (\Exception $e) {
      $data = [
        'type' => 'error',
        'title' => 'There was an error during save process',
        'description' => 'Contact technical support at support@opencontent.it'
      ];
      $this->logger->error(
        $e->getMessage(),
        ['request' => $request]
      );
      return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
    }
    return $this->view($subscription, Response::HTTP_CREATED);
  }

  /**
   * Edit full Subscription
   * @Rest\Put("/{id}", name="subscription_api_put_v2")
   *
   * @Security(name="Bearer")
   *
   * @OA\Parameter(
   *      name="version",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Version of Api, default 1. Version 1 is not available"
   *  )
   *
   * @OA\RequestBody(
   *     description="The subscription to edit",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             ref=@Model(type=Subscription::class, groups={"write"})
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=200,
   *     description="Edit full subscription"
   * )
   *
   * @OA\Response(
   *     response=400,
   *     description="Bad request"
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Not found"
   * )
   * @OA\Tag(name="subscriptions")
   *
   * @param Request $request
   * @param $id
   *
   * @return View
   */
  public function putSubscriptionAction($id, Request $request): View
  {
    $this->checkRequestedVersion($request);
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      SubcriptionsBackOffice::PATH,
      SubcriptionsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );

    $repository = $this->em->getRepository('App\Entity\Subscription');
    $subscription = $repository->find($id);

    if (!$subscription) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $this->denyAccessUnlessGranted(SubscriptionVoter::EDIT, $subscription);

    $form = $this->createForm('App\Form\SubscriptionType', $subscription);
    $this->processForm($request, $form);

    if ($form->isSubmitted() && !$form->isValid()) {
      $errors = FormUtils::getErrorsFromForm($form);
      $data = [
        'type' => 'put_validation_error',
        'title' => 'There was a validation error',
        'errors' => $errors
      ];
      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    try {
      $this->em->persist($subscription);
      $this->em->flush();
    } catch (UniqueConstraintViolationException $e) {
      $data = [
        'type' => 'error',
        'title' => 'Duplicated subscription',
        'description' => 'This subscription already exists'
      ];
      $this->logger->error(
        $e->getMessage(),
        ['request' => $request]
      );
      return $this->view($data, Response::HTTP_BAD_REQUEST);
    } catch (\Exception $e) {

      $data = [
        'type' => 'error',
        'title' => 'There was an error during save process',
        'description' => 'Contact technical support at support@opencontent.it'
      ];
      $this->logger->error(
        $e->getMessage(),
        ['request' => $request]
      );
      return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return $this->view(["Object Modified Successfully"], Response::HTTP_OK);
  }

  /**
   * Patch a Subscription
   * @Rest\Patch("/{id}", name="subscription_api_patch_v2")
   *
   * @Security(name="Bearer")
   *
   * @OA\Parameter(
   *      name="version",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Version of Api, default 1. Version 1 is not available"
   *  )
   *
   * @OA\RequestBody(
   *     description="The Subscription to patch",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             ref=@Model(type=Subscription::class, groups={"write"})
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=200,
   *     description="Patch a Subscription"
   * )
   *
   * @OA\Response(
   *     response=400,
   *     description="Bad request"
   * )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Not found"
   * )
   * @OA\Tag(name="subscriptions")
   *
   * @param Request $request
   * @param $id
   *
   * @return View
   */
  public function patchSubscriptionAction($id, Request $request)
  {
    $this->checkRequestedVersion($request);
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      SubcriptionsBackOffice::PATH,
      SubcriptionsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );

    $repository = $this->em->getRepository('App\Entity\Subscription');
    $subscription = $repository->find($id);

    if (!$subscription) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $this->denyAccessUnlessGranted(SubscriptionVoter::EDIT, $subscription);

    $form = $this->createForm('App\Form\SubscriptionType', $subscription);
    $this->processForm($request, $form);

    if ($form->isSubmitted() && !$form->isValid()) {
      $errors = FormUtils::getErrorsFromForm($form);
      $data = [
        'type' => 'validation_error',
        'title' => 'There was a validation error',
        'errors' => $errors
      ];
      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    try {
      $this->em->persist($subscription);
      $this->em->flush();
    } catch (UniqueConstraintViolationException $e) {
      $data = [
        'type' => 'error',
        'title' => 'Duplicated subscription',
        'description' => 'This subscription already exists'
      ];
      $this->logger->error(
        $e->getMessage(),
        ['request' => $request]
      );
      return $this->view($data, Response::HTTP_BAD_REQUEST);
    } catch (\Exception $e) {

      $data = [
        'type' => 'error',
        'title' => 'There was an error during save process',
        'description' => 'Contact technical support at support@opencontent.it'
      ];
      $this->logger->error(
        $e->getMessage(),
        ['request' => $request]
      );
      return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return $this->view(["Object Patched Successfully"], Response::HTTP_OK);
  }

  /**
   * @param Request $request
   * @param FormInterface $form
   */
  private function processForm(Request $request, FormInterface $form)
  {
    $data = json_decode($request->getContent(), true);

    $clearMissing = $request->getMethod() != 'PATCH';
    $form->submit($data, $clearMissing);
  }
}
