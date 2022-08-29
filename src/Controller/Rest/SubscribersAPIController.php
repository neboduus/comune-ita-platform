<?php


namespace App\Controller\Rest;


use App\BackOffice\SubcriptionsBackOffice;
use App\Entity\CPSUser;
use App\Entity\Subscriber;
use App\Entity\Subscription;
use App\Security\Voters\BackofficeVoter;
use App\Security\Voters\SubscriberVoter;
use App\Services\InstanceService;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
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
 * Class SubscribersAPIController
 * @property EntityManager em
 * @property InstanceService is
 * @package App\Controller
 * @Route("/subscribers")
 *
 */
class SubscribersAPIController extends AbstractApiController
{
  const SUPPORTED_API_VERSIONS = array(1);

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
   * Retrieve all Subscribers
   * @Rest\Get("", name="subscribers_api_get")
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
   *      name="fiscalCode",
   *      in="query",
   *      type="string",
   *      required=false,
   *      description="Subscriber fiscal code"
   *  )
   *
   * @SWG\Parameter(
   *      name="subscription",
   *      in="query",
   *      type="string",
   *      required=false,
   *      description="Subscription id"
   *  )
   *
   * @SWG\Response(
   *     response=200,
   *     description="Retreive all subscriptions",
   *      @Model(type=Subscriber::class, groups={"read"})
   * )
   *
   * @SWG\Response(
   *     response=403,
   *     description="Access denied"
   * )
   *
   * @SWG\Tag(name="subscribers")
   *
   * @return View
   */
  public function getSubscribersAction(Request $request): View
  {
    $this->checkRequestedVersion($request);
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      SubcriptionsBackOffice::PATH,
      SubcriptionsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );

    $user = $this->getUser();

    $fiscalCodeParameter = $request->get('fiscalCode', false);
    $subscriptionParameter = $request->get('subscription', false);

    $qb = $this->em->createQueryBuilder()
      ->select('subscriber')
      ->from(Subscriber::class, 'subscriber');


    if ($fiscalCodeParameter) {
      $qb->where('LOWER(subscriber.fiscal_code) = LOWER(:fiscalCode)')
        ->setParameter('fiscalCode', $fiscalCodeParameter);
    }

    if ($subscriptionParameter) {
      $qb->andWhere(':subscription MEMBER OF subscriber.subscriptions')
        ->setParameter('subscription', $subscriptionParameter);
    }


    // Filter by user permissions:
    if ($user instanceof CPSUser) {
      $expr = $this->em->getExpressionBuilder();
      $qb->join('subscriber.subscriptions', 'subscriptions')
        ->where($qb->expr()->orX(
          $expr->eq(
            'LOWER(:user)',
            'LOWER(subscriber.fiscal_code)'
          ),
          $expr->in(
            'LOWER(:user)',
            $this->em->createQueryBuilder()
              ->select('LOWER(JSONB_ARRAY_ELEMENTS_TEXT(subscriptions.relatedCFs))')
              ->from(Subscription::class, 's')
              ->getDQL()
          )
        ))
        ->setParameter('user', $user->getCodiceFiscale());
    }

    try {
      $subscribers = $qb->getQuery()->getResult();
    } catch (\Exception $e) {
      $data = [
        'type' => 'error',
        'title' => 'There was an error during save process',
        'description' => 'Contact technical support at support@opencontent.it'
      ];
      $this->logger->error($e->getMessage(), ['request' => $request]);
      return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
    }
    return $this->view($subscribers, Response::HTTP_OK);
  }

  /**
   * Retrieve a Subscriber
   * @Rest\Get("/{id}", name="subscriber_api_get")
   *
   * @SWG\Parameter(
   *     name="id",
   *     in="path",
   *     type="string",
   *     format="uuid",
   *     required=true,
   *     description="Subscriber's uuid",
   *     default="5365eab1-8741-43e6-bae1-9326da6734a2"
   * )
   * 
   * @SWG\Response(
   *     response=200,
   *     description="Retreive a Subscriber",
   *      @Model(type=Subscriber::class, groups={"read"})
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
   * @SWG\Tag(name="subscribers")
   *
   * @param Request $request
   * @param $id
   *
   * @return View
   */
  public function getSubscriberAction(Request $request, $id): View
  {
    $this->checkRequestedVersion($request);
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      SubcriptionsBackOffice::PATH,
      SubcriptionsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );

    try {
      $repository = $this->em->getRepository('App\Entity\Subscriber');
      $subscriber = $repository->find($id);
    } catch (\Exception $e) {
      $this->logger->error($e->getMessage(), ['request' => $request]);
      return $this->view(["Identifier conversion error"], Response::HTTP_BAD_REQUEST);
    }
    if ($subscriber === null) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }
    $this->denyAccessUnlessGranted(SubscriberVoter::VIEW, $subscriber);
    return $this->view($subscriber, Response::HTTP_OK);
  }

  /**
   * Create a Subscriber
   * @Rest\Post("", name="subscriber_api_post")
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
   *     name="Subscriber",
   *     in="body",
   *     description="The Subscriber to create",
   *     required=true,
   *     @SWG\Schema(
   *         type="object",
   *         ref=@Model(type=Subscriber::class, groups={"read"})
   *     )
   * )
   *
   * @SWG\Response(
   *     response=201,
   *     description="Create a Subscriber"
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
   * @SWG\Tag(name="subscribers")
   *
   * @param Request $request
   *
   * @return View
   */
  public function postSubscriberAction(Request $request): View
  {
    $this->checkRequestedVersion($request);
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      SubcriptionsBackOffice::PATH,
      SubcriptionsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );
    $this->denyAccessUnlessGranted(['ROLE_OPERATORE', 'ROLE_ADMIN']);

    $subscriber = new Subscriber();
    $form = $this->createForm('App\Form\SubscriberType', $subscriber);
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

    $subscribers = $this->em->createQueryBuilder()
      ->select('subscriber')
      ->from(Subscriber::class, 'subscriber')
      ->where('LOWER(subscriber.fiscal_code) = LOWER(:fiscal_code)')
      ->setParameter('fiscal_code', $subscriber->getFiscalCode())
      ->getQuery()->getResult();

    if ($subscribers) {
      $data = [
        'type' => 'error',
        'title' => 'Duplicated subscriber',
        'description' => 'This subscriber already exists'
      ];
      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    try {
      $this->em->persist($subscriber);
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
    return $this->view($subscriber, Response::HTTP_CREATED);
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

  /**
   * Delete a Subscriber
   * @Rest\Delete("/{id}", name="subscriber_api_delete")
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
   *     name="id",
   *     in="path",
   *     type="string",
   *     format="uuid",
   *     required=true,
   *     description="Subscriber's uuid",
   *     default="5365eab1-8741-43e6-bae1-9326da6734a2"
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
   * @SWG\Tag(name="subscribers")
   *
   * @param $id
   * @param Request $request
   *
   * @Method("DELETE")
   * @return View
   */
  public function deleteSubscriberAction(Request $request, $id): View
  {
    $this->checkRequestedVersion($request);
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      SubcriptionsBackOffice::PATH,
      SubcriptionsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );
    $this->denyAccessUnlessGranted(['ROLE_OPERATORE', 'ROLE_ADMIN']);

    $repository = $this->em->getRepository('App\Entity\Subscriber');
    $subscriber = $repository->find($id);
    if ($subscriber) {
      // debated point: should we 404 on an unknown nickname?
      // or should we just return a nice 204 in all cases?
      // we're doing the latter

      try {
        $this->em->remove($subscriber);
        $this->em->flush();
      } catch (ForeignKeyConstraintViolationException $e) {
        $data = [
          'type' => 'error',
          'title' => 'Related Subscriptions',
          'description' => 'This subscription has related subscriptions'
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
    }
    return $this->view(null, Response::HTTP_NO_CONTENT);
  }

  /**
   * Edit full Subscriber
   * @Rest\Put("/{id}", name="subscriber_api_put")
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
   *     name="id",
   *     in="path",
   *     type="string",
   *     format="uuid",
   *     required=true,
   *     description="Subscriber's uuid",
   *     default="5365eab1-8741-43e6-bae1-9326da6734a2"
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
   *     name="Subscriber",
   *     in="body",
   *     description="The subscriber to edit",
   *     required=true,
   *     @SWG\Schema(
   *         type="object",
   *         ref=@Model(type=Subscriber::class, groups={"write"})
   *     )
   * )
   *
   * @SWG\Response(
   *     response=200,
   *     description="Edit full subscriber"
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
   * @SWG\Tag(name="subscribers")
   *
   * @param Request $request
   * @param $id
   *
   * @return View
   */
  public function putSubscriberAction($id, Request $request): View
  {
    $this->checkRequestedVersion($request);
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      SubcriptionsBackOffice::PATH,
      SubcriptionsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );

    $repository = $this->em->getRepository('App\Entity\Subscriber');
    $subscriber = $repository->find($id);

    if (!$subscriber) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $this->denyAccessUnlessGranted(SubscriberVoter::EDIT, $subscriber);

    $form = $this->createForm('App\Form\SubscriberType', $subscriber, ['is_edit' => true]);

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
      $this->em->persist($subscriber);
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
   * Patch a Subscription
   * @Rest\Patch("/{id}", name="subscriber_api_patch")
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
   *     name="id",
   *     in="path",
   *     type="string",
   *     format="uuid",
   *     required=true,
   *     description="Subscriber's uuid",
   *     default="5365eab1-8741-43e6-bae1-9326da6734a2"
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
   *     name="Subscriber",
   *     in="body",
   *     description="The Subscriber to patch",
   *     required=true,
   *     @SWG\Schema(
   *         type="object",
   *         ref=@Model(type=Subscriber::class, groups={"write"})
   *     )
   * )
   *
   * @SWG\Response(
   *     response=200,
   *     description="Patch a Subscriber"
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
   * @SWG\Tag(name="subscribers")
   *
   * @param Request $request
   * @param $id
   *
   * @return View
   */
  public function patchSubscriberAction($id, Request $request)
  {
    $this->checkRequestedVersion($request);
    $this->denyAccessUnlessGranted(
      BackofficeVoter::VIEW,
      SubcriptionsBackOffice::PATH,
      SubcriptionsBackOffice::IDENTIFIER . ' integration is not enabled on current tenant'
    );

    $repository = $this->em->getRepository('App\Entity\Subscriber');
    $subscriber = $repository->find($id);

    if (!$subscriber) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $this->denyAccessUnlessGranted(SubscriberVoter::EDIT, $subscriber);

    $form = $this->createForm('App\Form\SubscriberType', $subscriber, ['is_edit' => true]);
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
      $this->em->persist($subscriber);
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
}
