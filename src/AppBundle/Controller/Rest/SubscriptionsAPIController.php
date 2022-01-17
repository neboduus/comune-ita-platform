<?php


namespace AppBundle\Controller\Rest;


use AppBundle\BackOffice\SubcriptionsBackOffice;
use AppBundle\Entity\CPSUser;
use AppBundle\Entity\Subscription;
use AppBundle\Entity\SubscriptionService;
use AppBundle\Security\Voters\BackofficeVoter;
use AppBundle\Security\Voters\SubscriptionVoter;
use AppBundle\Services\InstanceService;
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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Swagger\Annotations as SWG;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Nelmio\ApiDocBundle\Annotation\Model;


/**
 * Class SubscriptionsAPIController
 * @property EntityManager em
 * @property InstanceService is
 * @package AppBundle\Controller
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

  /**
   * Retrieve all Subscriptions
   * @Rest\Get("", name="subscriptions_api_get_v2")
   *
   * @SWG\Parameter(
   *     name="Authorization",
   *     in="header",
   *     description="The authentication Bearer",
   *     required=true,
   *     type="string"
   * )
   *
   * @SWG\Parameter(
   *      name="version",
   *      in="query",
   *      type="string",
   *      required=false,
   *      description="Version of Api, default 1. Version 1 is not available"
   *  )
   *
   * @SWG\Parameter(
   *      name="subscriptionService",
   *      in="query",
   *      type="string",
   *      required=false,
   *      description="Subscription service id"
   *  )
   *
   * @SWG\Parameter(
   *      name="code",
   *      in="query",
   *      type="string",
   *      required=false,
   *      description="Subscription service code"
   *  )
   *
   * @SWG\Parameter(
   *      name="tags",
   *      in="query",
   *      type="string",
   *      required=false,
   *      description="List of comma-separated subscription service tags"
   *  )
   *
   * @SWG\Parameter(
   *      name="subscriptionsAvailable",
   *      in="query",
   *      type="boolean",
   *      required=false,
   *      description="Filter by suscriptions availability"
   *  )
   *
   * @SWG\Parameter(
   *      name="subscriptionServiceStatus",
   *      in="query",
   *      type="integer",
   *      required=false,
   *      description="Subscription service status (available statuses are 0-pending, 1-active, 2-unactive)"
   *  )
   *
   * @SWG\Parameter(
   *      name="fiscalCode",
   *      in="query",
   *      type="string",
   *      required=false,
   *      description="Subscriber fiscal code"
   *  )
   *
   * @SWG\Response(
   *     response=200,
   *     description="Retreive all subscriptions",
   *      @Model(type=Subscription::class)
   * )
   *
   * @SWG\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @SWG\Tag(name="subscriptions")
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

    $qb = $this->em->createQueryBuilder()
      ->select('subscription')
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

    // Filter by user permissions
    if ($user instanceof CPSUser) {
      $expr = $this->em->getExpressionBuilder();
      $qb->andWhere($qb->expr()->orX(
        $expr->eq(
          'LOWER(:user)',
          'LOWER(subscriber.fiscal_code)'
        ),
        $expr->in(
          'LOWER(:user)',
          $this->em->createQueryBuilder()
            ->select('LOWER(JSONB_ARRAY_ELEMENTS_TEXT(s.relatedCFs))')
            ->from(Subscription::class, 's')
            ->getDQL()
        )
      ))
        ->setParameter('user', $user->getCodiceFiscale());
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
   * @SWG\Response(
   *     response=200,
   *     description="Retreive a Subscription",
   *      @Model(type=Subscription::class)
   * )
   *
   * @SWG\Parameter(
   *     name="Authorization",
   *     in="header",
   *     description="The authentication Bearer",
   *     required=true,
   *     type="string"
   * )
   *
   * @SWG\Parameter(
   *      name="version",
   *      in="query",
   *      type="string",
   *      required=false,
   *      description="Version of Api, default 1. Version 1 is not available"
   *  )
   *
   * @SWG\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @SWG\Response(
   *     response=404,
   *     description="Subscription not found"
   * )
   * @SWG\Tag(name="subscriptions")
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
      $repository = $this->getDoctrine()->getRepository('AppBundle:Subscription');
      $subscription = $repository->find($id);
    } catch (\Exception $e) {
      $this->logger->error($e->getMessage(), ['request' => $request]);
      return $this->view(["Identifier conversion error"], Response::HTTP_BAD_REQUEST);
    }
    if ($subscription === null) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }
    $this->denyAccessUnlessGranted(SubscriptionVoter::VIEW, $subscription);
    return $this->view($subscription, Response::HTTP_OK);
  }

  /**
   * Delete a Subscription
   * @Rest\Delete("/{id}", name="subscription_api_delete_v2")
   *
   * @SWG\Parameter(
   *     name="Authorization",
   *     in="header",
   *     description="The authentication Bearer",
   *     required=true,
   *     type="string"
   * )
   *
   * @SWG\Parameter(
   *      name="version",
   *      in="query",
   *      type="string",
   *      required=false,
   *      description="Version of Api, default 1. Version 1 is not available"
   *  )
   *
   * @SWG\Response(
   *     response=204,
   *     description="The resource was deleted successfully."
   * )
   *
   * @SWG\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @SWG\Tag(name="subscriptions")
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

    $repository = $this->getDoctrine()->getRepository('AppBundle:Subscription');
    $subscription = $repository->find($id);
    if ($subscription) {
      // debated point: should we 404 on an unknown nickname?
      // or should we just return a nice 204 in all cases?
      // we're doing the latter
      $em = $this->getDoctrine()->getManager();
      $em->remove($subscription);
      $em->flush();
    }
    return $this->view(null, Response::HTTP_NO_CONTENT);
  }

  /**
   * Create a Subscription
   * @Rest\Post("", name="subscription_api_post_v2")
   *
   * @SWG\Parameter(
   *     name="Authorization",
   *     in="header",
   *     description="The authentication Bearer",
   *     required=true,
   *     type="string"
   * )
   *
   * @SWG\Parameter(
   *      name="version",
   *      in="query",
   *      type="string",
   *      required=false,
   *      description="Version of Api, default 1. Version 1 is not available"
   *  )
   *
   * @SWG\Parameter(
   *     name="Subscription",
   *     in="body",
   *     type="json",
   *     description="The Subscription to create",
   *     required=true,
   *     @SWG\Schema(
   *         type="object",
   *         ref=@Model(type=Subscription::class)
   *     )
   * )
   *
   * @SWG\Response(
   *     response=201,
   *     description="Create a Subscription"
   * )
   *
   * @SWG\Response(
   *     response=400,
   *     description="Bad request"
   * )
   *
   * @SWG\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @SWG\Tag(name="subscriptions")
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
    $form = $this->createForm('AppBundle\Form\SubscriptionType', $subscription);
    $this->processForm($request, $form);

    if ($form->isSubmitted() && !$form->isValid()) {
      $errors = $this->getErrorsFromForm($form);
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
   * @SWG\Parameter(
   *     name="Authorization",
   *     in="header",
   *     description="The authentication Bearer",
   *     required=true,
   *     type="string"
   * )
   *
   * @SWG\Parameter(
   *      name="version",
   *      in="query",
   *      type="string",
   *      required=false,
   *      description="Version of Api, default 1. Version 1 is not available"
   *  )
   *
   * @SWG\Parameter(
   *     name="Subscription",
   *     in="body",
   *     type="json",
   *     description="The subscription to edit",
   *     required=true,
   *     @SWG\Schema(
   *         type="object",
   *         ref=@Model(type=Subscription::class)
   *     )
   * )
   *
   * @SWG\Response(
   *     response=200,
   *     description="Edit full subscription"
   * )
   *
   * @SWG\Response(
   *     response=400,
   *     description="Bad request"
   * )
   *
   * @SWG\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @SWG\Response(
   *     response=404,
   *     description="Not found"
   * )
   * @SWG\Tag(name="subscriptions")
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

    $repository = $this->getDoctrine()->getRepository('AppBundle:Subscription');
    $subscription = $repository->find($id);

    if (!$subscription) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $this->denyAccessUnlessGranted(SubscriptionVoter::EDIT, $subscription);

    $form = $this->createForm('AppBundle\Form\SubscriptionType', $subscription);
    $this->processForm($request, $form);

    if ($form->isSubmitted() && !$form->isValid()) {
      $errors = $this->getErrorsFromForm($form);
      $data = [
        'type' => 'put_validation_error',
        'title' => 'There was a validation error',
        'errors' => $errors
      ];
      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    try {
      $em = $this->getDoctrine()->getManager();
      $em->persist($subscription);
      $em->flush();
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
   * @SWG\Parameter(
   *     name="Authorization",
   *     in="header",
   *     description="The authentication Bearer",
   *     required=true,
   *     type="string"
   * )
   *
   * @SWG\Parameter(
   *      name="version",
   *      in="query",
   *      type="string",
   *      required=false,
   *      description="Version of Api, default 1. Version 1 is not available"
   *  )
   *
   * @SWG\Parameter(
   *     name="Subscription",
   *     in="body",
   *     type="json",
   *     description="The Subscription to patch",
   *     required=true,
   *     @SWG\Schema(
   *         type="object",
   *         ref=@Model(type=Subscription::class)
   *     )
   * )
   *
   * @SWG\Response(
   *     response=200,
   *     description="Patch a Subscription"
   * )
   *
   * @SWG\Response(
   *     response=400,
   *     description="Bad request"
   * )
   *
   * @SWG\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @SWG\Response(
   *     response=404,
   *     description="Not found"
   * )
   * @SWG\Tag(name="subscriptions")
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

    $repository = $this->getDoctrine()->getRepository('AppBundle:Subscription');
    $subscription = $repository->find($id);

    if (!$subscription) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $this->denyAccessUnlessGranted(SubscriptionVoter::EDIT, $subscription);

    $form = $this->createForm('AppBundle\Form\SubscriptionType', $subscription);
    $this->processForm($request, $form);

    if ($form->isSubmitted() && !$form->isValid()) {
      $errors = $this->getErrorsFromForm($form);
      $data = [
        'type' => 'validation_error',
        'title' => 'There was a validation error',
        'errors' => $errors
      ];
      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    try {
      $em = $this->getDoctrine()->getManager();
      $em->persist($subscription);
      $em->flush();
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

  /**
   * @param FormInterface $form
   * @return array
   */
  private function getErrorsFromForm(FormInterface $form): array
  {

    $errors = array();
    foreach ($form->getErrors() as $error) {
      $errors[] = $error->getMessage();
    }
    foreach ($form->all() as $childForm) {
      if ($childForm instanceof FormInterface) {
        if ($childErrors = $this->getErrorsFromForm($childForm)) {
          $errors[] = $childErrors;
        }
      }
    }
    return $errors;
  }
}
