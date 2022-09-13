<?php

namespace App\Controller\Rest;

use App\BackOffice\SubcriptionsBackOffice;
use App\Entity\SubscriptionService;
use App\Entity\Subscription;
use App\Model\SubscriptionPayment;
use App\Security\Voters\BackofficeVoter;
use App\Security\Voters\SubscriptionVoter;
use App\Services\InstanceService;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use OpenApi\Annotations as OA;


/**
 * Class SubscriptionsAPIController
 * @property EntityManagerInterface em
 * @property InstanceService is
 * @package App\Controller
 * @Route("/subscription-services")
 */
class SubscriptionServicesAPIController extends AbstractApiController
{
  const SUPPORTED_API_VERSIONS = array(1);

  /** @var EntityManagerInterface  */
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
   * List all SubscriptionService
   * @Rest\Get("", name="subscription-services_api_list")
   *
   * @OA\Parameter(
   *      name="available",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Filter results by subscription services availability. Availability is computed on service status, subscriptions due date and subscriptions limit"
   *  )
   *
   * @OA\Parameter(
   *      name="tags",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Filter results by subscription services tags (Comma separated values)"
   *  )
   *
   * @OA\Response(
   *     response=200,
   *     description="Retrieve list of SubscriptionServices",
   *     @OA\JsonContent(
   *         type="array",
   *         @OA\Items(ref=@Model(type=SubscriptionService::class))
   *     )
   * )
   * @OA\Tag(name="subscription-services")
   */
  public function getSubscriptionServicesAction(Request $request)
  {
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      SubcriptionsBackOffice::PATH,
      SubcriptionsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );

    $results = [];
    $tags = array_filter(explode(',', $request->query->get('tags')));
    sort($tags);

    if (strtolower($request->query->get('available')) == 'true') {
      $subscriptionServices = $this->em->createQueryBuilder()
        ->select('t1')
        ->from('App:SubscriptionService', 't1')
        ->leftJoin('t1.subscriptions', 't2')
        ->groupBy('t1.id')
        ->having('COUNT(t2) < t1.subscribersLimit OR t1.subscribersLimit is NULL')
        ->where('t1.subscriptionEnd >= :now')
        ->andWhere('t1.status = 1')
        ->setParameter('now', new \DateTime())
        ->orderBy('t1.name', "ASC")
        ->getQuery()->getResult();
    } else {
      $subscriptionServices = $this->em->getRepository('App\Entity\SubscriptionService')->findAll();
    }

    foreach ($subscriptionServices as $subscriptionService) {
      $serviceTags = $subscriptionService->getTags();
      sort($serviceTags);
      if (!$tags || array_intersect($tags, $serviceTags) == $tags) {
        $results[] = $subscriptionService;
      }
    }
    return $this->view(['results' => $results, 'count' => count($results)], Response::HTTP_OK);
  }

  /**
   * Retreive a SubscriptionService
   * @Rest\Get("/{id}", name="subscription-services_api_get")
   *
   * @OA\Response(
   *     response=200,
   *     description="Retreive a SubscriptionService",
   *     @Model(type=SubscriptionService::class)
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Subscription Service not found"
   * )
   * @OA\Tag(name="subscription-services")
   *
   * @param $id
   * @return View
   */
  public function getSubscriptionServiceAction($id)
  {
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      SubcriptionsBackOffice::PATH,
      SubcriptionsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );

    try {
      $repository = $this->em->getRepository('App\Entity\SubscriptionService');
      $result = $repository->find($id);
      if ($result === null) {
        return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
      }
      return $this->view($result, Response::HTTP_OK);
    } catch (\Exception $e) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }
  }

  /**
   * Retreive a SubscriptionService's Payment settings
   * @Rest\Get("/{id}/payment-settings", name="subscription-service_payment-settings_api_get")
   *
   * @OA\Parameter(
   *      name="required",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Filter results by payment mandatory option"
   *  )
   *
   * @OA\Parameter(
   *      name="create_draft",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Filter results by payment draft creation option"
   *  )
   *
   * @OA\Parameter(
   *      name="subscription_fee",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Filter results by payment type (subscription fee or additional payment)"
   *  )
   *
   * @OA\Parameter(
   *      name="availbale",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Filter results by payment availability (future payments)"
   *  )
   *
   * @OA\Parameter(
   *      name="identifier",
   *      in="query",
   *      @OA\Schema(
   *          type="string"
   *      ),
   *      required=false,
   *      description="Filter results by subscription payment identifier"
   *  )
   *
   * @OA\Response(
   *     response=200,
   *     description="Retreive a SubscriptionService's payments",
   *     @Model(type=SubscriptionPayment::class)
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Subscription Service not found"
   * )
   * @OA\Tag(name="subscription-services")
   *
   * @param $id
   * @return View
   */
  public function getSubscriptionServicePaymentsAction(Request $request, $id)
  {
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      SubcriptionsBackOffice::PATH,
      SubcriptionsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );

    $required = $request->query->get('required');
    $create_draft = $request->query->get('create_draft');
    $identifier = $request->query->get('identifier');
    $subscriptionFee = $request->query->get('subscription_fee');
    $available = $request->query->get('available');

    if ($required) {
     $required = strtolower($request->query->get('required')) === "true";
    }
    if ($create_draft) {
     $create_draft = strtolower($request->query->get('create_draft')) === "true";
    }
    if ($subscriptionFee) {
     $subscriptionFee = strtolower($request->query->get('subscription_fee')) === "true";
    }
    if ($available) {
      $available = strtolower($available) === "true";
    }

    try {
      $subscriptionService = $this->em->getRepository(SubscriptionService::class)->find($id);

      if ($subscriptionService === null) {
        return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
      }

      $paymentSettings = [];

      foreach ($subscriptionService->getSubscriptionPayments() as $paymentSetting) {
        $canAdd = true;

        if (!is_null($required)  && $required !== $paymentSetting->isRequired()) {
          $canAdd = false;
        }
        if (!is_null($create_draft) && $create_draft !== $paymentSetting->getCreateDraft()) {
          $canAdd = false;
        }
        if (!is_null($subscriptionFee) && $subscriptionFee !== $paymentSetting->isSubscriptionFee()) {
          $canAdd = false;
        }
        if (!is_null($identifier) && $identifier !== $paymentSetting->getPaymentIdentifier()) {
          $canAdd = false;
        }
        if($available && $paymentSetting->getDate() < (new \DateTime())->setTime(0,0,0)) {
          $canAdd = false;
        }

        if ($canAdd) {
          $paymentSettings[] = $paymentSetting;
        }
      }

      return $this->view($paymentSettings, Response::HTTP_OK);
    } catch (\Exception $e) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }
  }

  /**
   * Create a SubscriptionService
   * @Rest\Post(name="subscription-services_api_post")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The SubscriptionService to create",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             ref=@Model(type=SubscriptionService::class)
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=201,
   *     description="Create a SubscriptionService"
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
   * @OA\Tag(name="subscription-services")
   *
   * @param Request $request
   * @return View
   * @throws \Exception
   */
  public function postSubscriptionServiceAction(Request $request)
  {
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      SubcriptionsBackOffice::PATH,
      SubcriptionsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );
    $this->denyAccessUnlessGranted(['ROLE_OPERATORE','ROLE_ADMIN' ]);

    $subscriptionService = new SubscriptionService();
    $form = $this->createForm('App\Form\SubscriptionServiceType', $subscriptionService);
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
      $this->em->persist($subscriptionService);
      $this->em->flush();
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

    return $this->view($subscriptionService, Response::HTTP_CREATED);
  }

  /**
   * Edit full SubscriptionService
   * @Rest\Put("/{id}", name="subscription-services_api_put")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The subscriptionService to create",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             ref=@Model(type=SubscriptionService::class)
   *         )
   *     ) 
   * )
   *
   * @OA\Response(
   *     response=200,
   *     description="Edit full subscriptionService"
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
   * @OA\Tag(name="subscription-services")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function putSubscriptionServiceAction($id, Request $request)
  {
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      SubcriptionsBackOffice::PATH,
      SubcriptionsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );
    $this->denyAccessUnlessGranted(['ROLE_OPERATORE','ROLE_ADMIN' ]);

    $repository = $this->em->getRepository('App\Entity\SubscriptionService');
    $subscriptionService = $repository->find($id);

    if (!$subscriptionService) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $form = $this->createForm('App\Form\SubscriptionServiceType', $subscriptionService);
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
      $this->em->persist($subscriptionService);
      $this->em->flush();
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
   * Patch a SubscriptionService
   * @Rest\Patch("/{id}", name="subscription-services_api_patch")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The Subscription Service to patch",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             ref=@Model(type=SubscriptionService::class)
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=200,
   *     description="Patch a SubscriptionService"
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
   * @OA\Tag(name="subscription-services")
   *
   * @param $id
   * @param Request $request
   * @return View
   * @throws \Exception
   */
  public function patchSubscriptionServiceAction($id, Request $request)
  {
    $this->denyAccessUnlessGranted(['ROLE_OPERATORE','ROLE_ADMIN' ]);

    $repository = $this->em->getRepository('App\Entity\SubscriptionService');
    $subscriptionService = $repository->find($id);

    if (!$subscriptionService) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }
    $form = $this->createForm('App\Form\SubscriptionServiceType', $subscriptionService);
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
      $this->em->persist($subscriptionService);
      $this->em->flush();
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
   * Delete a Service
   * @Rest\Delete("/{id}", name="subscription-services_api_delete")
   *
   * @Security(name="Bearer")
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
   * @OA\Tag(name="subscription-services")
   *
   * @Method("DELETE")
   * @param $id
   * @return View
   */
  public function deleteAction($id)
  {
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      SubcriptionsBackOffice::PATH,
      SubcriptionsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );
    $this->denyAccessUnlessGranted(['ROLE_OPERATORE','ROLE_ADMIN' ]);

    $subscriptionService = $this->em->getRepository('App\Entity\SubscriptionService')->find($id);
    if ($subscriptionService) {
      // debated point: should we 404 on an unknown nickname?
      // or should we just return a nice 204 in all cases?
      // we're doing the latter
      $this->em->remove($subscriptionService);
      $this->em->flush();
    } else {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }
    return $this->view(null, Response::HTTP_NO_CONTENT);
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
  private function getErrorsFromForm(FormInterface $form)
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


  /**
   * Retrieve all Subscriptions of a Subscription Service
   * @Rest\Get("/{subscription_service_id}/subscriptions", name="subscriptions_api_get")
   *
   * @OA\Response(
   *     response=200,
   *     description="Retreive the Subscriptions of a Subscription Service",
   *   @Model(type=Subscription::class, groups={"read"})
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
   *      description="Version of Api, default 1"
   *  )
   *
   * @OA\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Subscriptions not found"
   * )
   * @OA\Tag(name="subscriptions")
   * @param Request $request
   * @param $subscription_service_id
   *
   * @return View
   */
  public function getSubscriptionsAction(Request $request, $subscription_service_id): View
  {
    $this->checkRequestedVersion($request);
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      SubcriptionsBackOffice::PATH,
      SubcriptionsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );
    $this->denyAccessUnlessGranted(['ROLE_OPERATORE','ROLE_ADMIN' ]);

    try {
      $repository = $this->em->getRepository('App\Entity\SubscriptionService');
      $subscriptionService = $repository->find($subscription_service_id);
      if ($subscriptionService === null) {
        return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
      }
      return $this->view(['results' => $subscriptionService->getSubscriptions(), 'count' => count($subscriptionService->getSubscriptions())], Response::HTTP_OK);
    } catch (\Exception $e) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }
  }

  /**
   * Retrieve a Subscription of a SubscriptionService
   * @Rest\Get("/{subscription_service_id}/subscriptions/{id}", name="subscription_api_get")
   *
   * @OA\Response(
   *     response=200,
   *     description="Retreive a Subscription of a SubscriptionService",
   *      @Model(type=Subscription::class, groups={"read"})
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
   *      description="Version of Api, default 1"
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
   * @param $subscription_service_id
   * @param $id
   *
   * @return View
   */
  public function getSubscriptionAction(Request $request, $subscription_service_id, $id): View
  {
    $this->checkRequestedVersion($request);
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      SubcriptionsBackOffice::PATH,
      SubcriptionsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );

    try {
      $repository = $this->em->getRepository('App\Entity\Subscription');
      $subscription = $repository->findOneBy(['subscription_service' => $subscription_service_id, 'id' => $id]);
    } catch (\Exception $e) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }
    if ($subscription === null) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }
    $this->denyAccessUnlessGranted(SubscriptionVoter::VIEW, $subscription);
    return $this->view($subscription, Response::HTTP_OK);
  }

  /**
   * Delete a Subscription
   * @Rest\Delete("/{subscription_service_id}/subscriptions/{id}", name="subscription_api_delete")
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
   *      description="Version of Api, default 1"
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
   * @param Request $request
   * @param $subscription_service_id
   * @param $id
   *
   * @return View
   * @Method("DELETE")
   */
  public function deleteSubscriptionAction(Request $request, $subscription_service_id, $id): View
  {
    $this->checkRequestedVersion($request);
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      SubcriptionsBackOffice::PATH,
      SubcriptionsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );
    $this->denyAccessUnlessGranted(['ROLE_OPERATORE','ROLE_ADMIN' ]);

    $repository = $this->em->getRepository('App\Entity\Subscription');

    $subscription = $repository->findOneBy(['subscription_service' => $subscription_service_id, 'id' => $id]);
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
   * @Rest\Post("/{subscription_service_id}/subscriptions", name="subscription_api_post")
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
   *      description="Version of Api, default 1"
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
    $this->denyAccessUnlessGranted(['ROLE_OPERATORE','ROLE_ADMIN' ]);

    $subscription = new Subscription();
    $form = $this->createForm('App\Form\SubscriptionType', $subscription);
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
    return $this->view($subscription, Response::HTTP_CREATED);
  }

  /**
   * Edit full Subscription
   * @Rest\Put("/{subscription_service_id}/subscriptions/{id}", name="subscription_api_put")
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
   *      description="Version of Api, default 1"
   *  )
   *
   * @OA\RequestBody(
   *     description="The subscription to create",
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
   * @param $subscription_service_id
   * @param $id
   *
   * @return View
   */
  public function putSubscriptionAction($subscription_service_id, $id, Request $request): View
  {
    $this->checkRequestedVersion($request);
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      SubcriptionsBackOffice::PATH,
      SubcriptionsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );

    $repository = $this->em->getRepository('App\Entity\Subscription');
    $subscription = $repository->findOneBy(['subscription_service' => $subscription_service_id, 'id' => $id]);

    if (!$subscription) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $this->denyAccessUnlessGranted(SubscriptionVoter::EDIT, $subscription);

    $form = $this->createForm('App\Form\SubscriptionType', $subscription);
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
   * @Rest\Patch("/{subscription_service_id}/subscriptions/{id}", name="subscription_api_patch")
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
   *      description="Version of Api, default 1"
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
   * @param $subscription_service_id
   * @param $id
   *
   * @return View
   */
  public function patchSubscriptionAction($subscription_service_id, $id, Request $request): View
  {
    $this->checkRequestedVersion($request);
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      SubcriptionsBackOffice::PATH,
      SubcriptionsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );

    $repository = $this->em->getRepository('App\Entity\Subscription');
    $subscription = $repository->findOneBy(['subscription_service' => $subscription_service_id, 'id' => $id]);

    if (!$subscription) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $this->denyAccessUnlessGranted(SubscriptionVoter::EDIT, $subscription);

    $form = $this->createForm('App\Form\SubscriptionType', $subscription);
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
    }  catch (\Exception $e) {
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
}
