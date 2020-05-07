<?php

namespace App\Controller\Rest;

use App\Entity\Subscriber;
use App\Entity\SubscriptionService;
use App\Entity\Subscription;
use App\Multitenancy\TenantAwareFOSRestController;
use App\Services\InstanceService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use App\Multitenancy\Annotations\MustHaveTenant;

/**
 * Class SubscriptionsAPIController
 * @property EntityManager em
 * @property InstanceService is
 * @package App\Controller
 * @Route("/subscription-services")
 * @MustHaveTenant()
 */
class SubscriptionServicesAPIController extends TenantAwareFOSRestController
{
    const CURRENT_API_VERSION = '1.0';

    public function __construct(EntityManagerInterface $em, InstanceService $is)
    {
        $this->em = $em;
        $this->is = $is;
    }

    /**
     * List all SubscriptionService
     * @Rest\Get("", name="subscription-services_api_list")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Retrieve list of SubscriptionServices",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=SubscriptionService::class))
     *     )
     * )
     * @SWG\Tag(name="subscription-services")
     *
     * @param Request $request
     * @return View
     * @throws \Exception
     */
    public function getSubscriptionServices(Request $request)
    {
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
                ->getQuery()->getResult();
        } else {
            $subscriptionServices = $this->getDoctrine()->getRepository('App:SubscriptionService')->findAll();
        }

        /** @var SubscriptionService $subscriptionService */
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
     * @SWG\Response(
     *     response=200,
     *     description="Retreive a SubscriptionService",
     *     @Model(type=SubscriptionService::class)
     * )
     *
     * @SWG\Response(
     *     response=404,
     *     description="Subscription Service not found"
     * )
     * @SWG\Tag(name="subscription-services")
     *
     * @param $id
     * @return View
     */
    public function getSubscriptionService($id)
    {
        try {
            $repository = $this->getDoctrine()->getRepository('App:SubscriptionService');
            $result = $repository->find($id);
            if ($result === null) {
                return $this->view("Object not found", Response::HTTP_NOT_FOUND);
            }
            return $this->view($result, Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->view("Object not found", Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Create a SubscriptionService
     * @Rest\Post(name="subscription-services_api_post")
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
     *     name="SubscriptionService",
     *     in="body",
     *     type="json",
     *     description="The SubscriptionService to create",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         ref=@Model(type=SubscriptionService::class)
     *     )
     * )
     *
     * @SWG\Response(
     *     response=201,
     *     description="Create a SubscriptionService"
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="Bad request"
     * )
     * @SWG\Tag(name="subscription-services")
     *
     * @param Request $request
     * @return View
     * @throws \Exception
     */
    public function postSubscriptionService(Request $request)
    {
        $subscriptionService = new SubscriptionService();
        $form = $this->createForm('App\Form\SubscriptionServiceType', $subscriptionService);
        $this->processForm($request, $form);

        if (!$form->isValid()) {
            $errors = $this->getErrorsFromForm($form);
            $data = [
                'type' => 'validation_error',
                'title' => 'There was a validation error',
                'errors' => $errors
            ];
            return $this->view($data, Response::HTTP_BAD_REQUEST);
        }

        $em = $this->getDoctrine()->getManager();

        try {
            $em->persist($subscriptionService);
            $em->flush();
        } catch (\Exception $e) {
            $data = [
                'type' => 'error',
                'title' => 'There was an error during save process',
                'description' => $e->getMessage()
            ];
            $this->get('logger')->error(
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
     * @SWG\Parameter(
     *     name="Authorization",
     *     in="header",
     *     description="The authentication Bearer",
     *     required=true,
     *     type="string"
     * )
     *
     * @SWG\Parameter(
     *     name="SubscriptionService",
     *     in="body",
     *     type="json",
     *     description="The subscriptionService to create",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         ref=@Model(type=SubscriptionService::class)
     *     )
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Edit full subscriptionService"
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="Bad request"
     * )
     *
     * @SWG\Response(
     *     response=404,
     *     description="Not found"
     * )
     * @SWG\Tag(name="subscription-services")
     *
     * @param $id
     * @param Request $request
     * @return View
     */
    public function putSubscriptionService($id, Request $request)
    {
        $repository = $this->getDoctrine()->getRepository('App:SubscriptionService');
        $subscriptionService = $repository->find($id);

        if (!$subscriptionService) {
            return $this->view("Object not found", Response::HTTP_NOT_FOUND);
        }

        $form = $this->createForm('App\Form\SubscriptionServiceType', $subscriptionService);
        $this->processForm($request, $form);

        if (!$form->isValid()) {
            $errors = $this->getErrorsFromForm($form);
            $data = [
                'type' => 'put_validation_error',
                'title' => 'There was a validation error',
                'errors' => $errors
            ];
            return $this->view($data, Response::HTTP_BAD_REQUEST);
        }

        $em = $this->getDoctrine()->getManager();

        try {
            $em->persist($subscriptionService);
            $em->flush();
        } catch (\Exception $e) {
            $data = [
                'type' => 'error',
                'title' => $e->getMessage()
            ];
            $this->get('logger')->error(
                $e->getMessage(),
                ['request' => $request]
            );
            return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->view("Object Modified Successfully", Response::HTTP_OK);
    }

    /**
     * Patch a SubscriptionService
     * @Rest\Patch("/{id}", name="subscription-services_api_patch")
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
     *     name="SubscriptionService",
     *     in="body",
     *     type="json",
     *     description="The Subscription Service to patch",
     *     required=true,
     *     @SWG\Schema(
     *         type="object",
     *         ref=@Model(type=SubscriptionService::class)
     *     )
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Patch a SubscriptionService"
     * )
     *
     * @SWG\Response(
     *     response=400,
     *     description="Bad request"
     * )
     *
     * @SWG\Response(
     *     response=404,
     *     description="Not found"
     * )
     * @SWG\Tag(name="subscription-services")
     *
     * @param $id
     * @param Request $request
     * @return View
     * @throws \Exception
     */
    public function patchSubscriptionService($id, Request $request)
    {
        $repository = $this->getDoctrine()->getRepository('App:SubscriptionService');
        $subscriptionService = $repository->find($id);

        if (!$subscriptionService) {
            return $this->view("Object not found", Response::HTTP_NOT_FOUND);
        }
        $form = $this->createForm('App\Form\SubscriptionServiceType', $subscriptionService);
        $this->processForm($request, $form);

        if (!$form->isValid()) {
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
            $em->persist($subscriptionService);
            $em->flush();
        } catch (\Exception $e) {
            $data = [
                'type' => 'error',
                'title' => 'There was an error during save process'
            ];
            $this->get('logger')->error(
                $e->getMessage(),
                ['request' => $request]
            );
            return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->view("Object Patched Successfully", Response::HTTP_OK);
    }

    /**
     * Delete a Service
     * @Rest\Delete("/{id}", name="subscription-services_api_delete", methods={"DELETE"})
     *
     * @SWG\Parameter(
     *     name="Authorization",
     *     in="header",
     *     description="The authentication Bearer",
     *     required=true,
     *     type="string"
     * )
     *
     * @SWG\Response(
     *     response=204,
     *     description="The resource was deleted successfully."
     * )
     * @SWG\Tag(name="subscription-services")
     *
     * @param $id
     * @return View
     */
    public function delete($id)
    {
        $subscriptionService = $this->getDoctrine()->getRepository('App:SubscriptionService')->find($id);
        if ($subscriptionService) {
            // debated point: should we 404 on an unknown nickname?
            // or should we just return a nice 204 in all cases?
            // we're doing the latter
            $em = $this->getDoctrine()->getManager();
            $em->remove($subscriptionService);
            $em->flush();
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
                    echo '<pre>';
                    print_r($childErrors);
                    echo '</pre>';
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
     * @SWG\Response(
     *     response=200,
     *     description="Retreive the Subscriptions of a Subscription Service",
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
     * @SWG\Response(
     *     response=404,
     *     description="Subscriptions not found"
     * )
     * @SWG\Tag(name="subscriptions")
     * @param $subscription_service_id
     *
     * @return View
     */
    public function getSubscriptions($subscription_service_id)
    {
        try {
            $repository = $this->getDoctrine()->getRepository('App:SubscriptionService');
            $subscriptionService = $repository->find($subscription_service_id);
            if ($subscriptionService === null) {
                return $this->view("Object not found", Response::HTTP_NOT_FOUND);
            }
            return $this->view(['results' => $subscriptionService->getSubscriptions(), 'count' => count($subscriptionService->getSubscriptions())], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->view("Object not found", Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Retrieve a Subscription of a SubscriptionService
     * @Rest\Get("/{subscription_service_id}/subscriptions/{id}", name="subscription_api_get")
     *
     * @SWG\Response(
     *     response=200,
     *     description="Retreive a Subscription of a SubscriptionService",
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
     * @SWG\Response(
     *     response=404,
     *     description="Subscription not found"
     * )
     * @SWG\Tag(name="subscriptions")
     *
     * @param $subscription_service_id
     * @param $id
     *
     * @return View
     */
    public function getSubscription($subscription_service_id, $id)
    {
        try {
            $repository = $this->getDoctrine()->getRepository('App:Subscription');
            $subscription = $repository->findOneBy(['subscription_service' => $subscription_service_id, 'id' => $id]);

            if ($subscription === null) {
                return $this->view("Object not found", Response::HTTP_NOT_FOUND);
            }
            return $this->view($subscription, Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->view("Object not found", Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Delete a Subscription
     * @Rest\Delete("/{subscription_service_id}/subscriptions/{id}", name="subscription_api_delete", methods={"DELETE"})
     *
     * @SWG\Parameter(
     *     name="Authorization",
     *     in="header",
     *     description="The authentication Bearer",
     *     required=true,
     *     type="string"
     * )
     *
     * @SWG\Response(
     *     response=204,
     *     description="The resource was deleted successfully."
     * )
     * @SWG\Tag(name="subscriptions")
     *
     * @param $subscription_service_id
     * @param $id
     *
     * @return View
     */
    public function deleteSubscription($subscription_service_id, $id)
    {
        $repository = $this->getDoctrine()->getRepository('App:Subscription');
        $subscription = $repository->findOneBy(['subscription_service' => $subscription_service_id, 'id' => $id]);
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
     * @Rest\Post("/{subscription_service_id}/subscriptions", name="subscription_api_post")
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
     * @SWG\Tag(name="subscriptions")
     *
     * @param Request $request
     *
     * @return View
     */
    public function postSubscription(Request $request)
    {
        $subscription = new Subscription();
        $form = $this->createForm('App\Form\SubscriptionType', $subscription);
        $this->processForm($request, $form);

        if (!$form->isValid()) {
            $errors = $this->getErrorsFromForm($form);
            $data = [
                'type' => 'validation_error',
                'title' => 'There was a validation error',
                'errors' => $errors
            ];
            return $this->view($data, Response::HTTP_BAD_REQUEST);
        }

        /** @var Subscriber $subscriber */
        $subscriber = $this->em->getRepository('App:Subscriber')->findOneBy(['fiscal_code' => $subscription->getSubscriber()->getFiscalCode()]);

        if (!$subscriber) {
            try {
                $this->em->persist($subscription->getSubscriber());
                $this->em->flush();
            } catch (\Exception $e) {
                $data = [
                    'type' => 'error',
                    'title' => 'There was an error during save process',
                    'description' => $e->getMessage()
                ];
                $this->get('logger')->error(
                    $e->getMessage(),
                    ['request' => $request]
                );
                return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } else {
            $subscription->setSubscriber($subscriber);
        }

        try {
            $this->em->persist($subscription);
            $this->em->flush();
        } catch (\Exception $e) {
            $data = [
                'type' => 'error',
                'title' => 'There was an error during save process',
                'description' => $e->getMessage()
            ];
            $this->get('logger')->error(
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
     * @SWG\Parameter(
     *     name="Authorization",
     *     in="header",
     *     description="The authentication Bearer",
     *     required=true,
     *     type="string"
     * )
     *
     * @SWG\Parameter(
     *     name="Subscription",
     *     in="body",
     *     type="json",
     *     description="The subscription to create",
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
     *     response=404,
     *     description="Not found"
     * )
     * @SWG\Tag(name="subscriptions")
     *
     * @param Request $request
     * @param $subscription_service_id
     * @param $id
     *
     * @return View
     */
    public function putSubscription($subscription_service_id, $id, Request $request)
    {
        $repository = $this->getDoctrine()->getRepository('App:Subscription');
        $subscription = $repository->findOneBy(['subscription_service' => $subscription_service_id, 'id' => $id]);

        if (!$subscription) {
            return $this->view("Object not found", Response::HTTP_NOT_FOUND);
        }
        $form = $this->createForm('App\Form\SubscriptionType', $subscription);
        $this->processForm($request, $form);

        if (!$form->isValid()) {
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
        } catch (\Exception $e) {
            $data = [
                'type' => 'error',
                'title' => $e->getMessage()
            ];
            $this->get('logger')->error(
                $e->getMessage(),
                ['request' => $request]
            );
            return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->view("Object Modified Successfully", Response::HTTP_OK);
    }

    /**
     * Patch a Subscription
     * @Rest\Patch("/{subscription_service_id}/subscriptions/{id}", name="subscription_api_patch")
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
     *     response=404,
     *     description="Not found"
     * )
     * @SWG\Tag(name="subscriptions")
     *
     * @param Request $request
     * @param $subscription_service_id
     * @param $id
     *
     * @return View
     */
    public function patchSubscription($subscription_service_id, $id, Request $request)
    {
        $repository = $this->getDoctrine()->getRepository('App:Subscription');
        $subscription = $repository->findOneBy(['subscription_service' => $subscription_service_id, 'id' => $id]);

        if (!$subscription) {
            return $this->view("Object not found", Response::HTTP_NOT_FOUND);
        }
        $form = $this->createForm('App\Form\SubscriptionType', $subscription);
        $this->processForm($request, $form);

        if (!$form->isValid()) {
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
        } catch (\Exception $e) {
            $data = [
                'type' => 'error',
                'title' => 'There was an error during save process'
            ];
            $this->get('logger')->error(
                $e->getMessage(),
                ['request' => $request]
            );
            return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->view("Object Patched Successfully", Response::HTTP_OK);
    }
}
