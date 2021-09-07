<?php

namespace AppBundle\Controller\Rest;

use AppBundle\Entity\Categoria;
use AppBundle\Entity\Ente;
use AppBundle\Entity\Erogatore;
use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\PraticaRepository;
use AppBundle\Entity\ServiceGroup;
use AppBundle\Logging\LogConstants;
use AppBundle\Model\PaymentParameters;
use AppBundle\Model\FlowStep;
use AppBundle\Model\AdditionalData;
use AppBundle\Entity\Servizio;
use AppBundle\Dto\Service;
use AppBundle\Protocollo\ProtocolloHandlerRegistry;
use AppBundle\Services\FormServerApiAdapterService;
use AppBundle\Services\InstanceService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Form\FormInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * Class ServicesAPIController
 * @property EntityManagerInterface em
 * @property InstanceService is
 * @package AppBundle\Controller
 * @Route("/services")
 */
class ServicesAPIController extends AbstractFOSRestController
{
  const CURRENT_API_VERSION = '1.0';

  /** @var EntityManagerInterface */
  private $em;

  /** @var InstanceService */
  private $is;

  /** @var LoggerInterface */
  private $logger;
  /**
   * @var FormServerApiAdapterService
   */
  private $formServerApiAdapterService;
  /**
   * @var ProtocolloHandlerRegistry
   */
  private $handlerRegistry;

  private $handlerList = [];

  /**
   * ServicesAPIController constructor.
   * @param EntityManagerInterface $em
   * @param InstanceService $is
   * @param LoggerInterface $logger
   * @param FormServerApiAdapterService $formServerApiAdapterService
   * @param ProtocolloHandlerRegistry $handlerRegistry
   */
  public function __construct(EntityManagerInterface $em, InstanceService $is, LoggerInterface $logger, FormServerApiAdapterService $formServerApiAdapterService, ProtocolloHandlerRegistry $handlerRegistry)
  {
    $this->em = $em;
    $this->is = $is;
    $this->logger = $logger;
    $this->formServerApiAdapterService = $formServerApiAdapterService;
    $this->handlerRegistry = $handlerRegistry;

    foreach ($this->handlerRegistry->getAvailableHandlers() as $alias => $handler){
      $this->handlerList[] = $alias;
    }
  }


  /**
   * List all Services
   * @Rest\Get("", name="services_api_list")
   *
   * @SWG\Parameter(
   *      name="service_group_id",
   *      in="query",
   *      type="string",
   *      required=false,
   *      description="Id of the service group"
   *  )
   *
   * @SWG\Response(
   *     response=200,
   *     description="Retrieve list of services",
   *     @SWG\Schema(
   *         type="array",
   *         @SWG\Items(ref=@Model(type=Service::class))
   *     )
   * )
   * @SWG\Tag(name="services")
   */
  public function getServicesAction(Request $request)
  {

    try {
      $serviceGroupId = $request->get('service_group_id', false);
      $result = [];
      $repoServices = $this->em->getRepository(Servizio::class);
      $criteria['status'] = Servizio::PUBLIC_STATUSES;

      if ($serviceGroupId) {
        $serviceGroupRepo = $this->em->getRepository('AppBundle:ServiceGroup');
        $serviceGroup = $serviceGroupRepo->find($serviceGroupId);
        if (!$serviceGroup instanceof ServiceGroup) {
          return $this->view(["Service group not found"], Response::HTTP_NOT_FOUND);
        }

        $criteria['serviceGroup'] = $serviceGroupId;
      }

      $services = $repoServices->findBy($criteria);
      foreach ($services as $s) {
        $result [] = Service::fromEntity($s, $this->formServerApiAdapterService->getFormServerPublicUrl());
      }

      return $this->view($result, Response::HTTP_OK);
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

  /**
   * Retreive a Service
   * @Rest\Get("/{id}", name="service_api_get")
   *
   * @SWG\Response(
   *     response=200,
   *     description="Retreive a Service",
   *     @Model(type=Service::class)
   * )
   *
   * @SWG\Response(
   *     response=404,
   *     description="Service not found"
   * )
   * @SWG\Tag(name="services")
   *
   * @param $id
   * @return View
   */
  public function getServiceAction($id)
  {
    try {
      $repository = $this->getDoctrine()->getRepository('AppBundle:Servizio');
      $result = $repository->find($id);
      if ($result === null) {
        return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
      }

      if ($result->getStatus() == Servizio::STATUS_CANCELLED) {
        return $this->view(["You are not allowed to see this service"], Response::HTTP_FORBIDDEN);
      }

      return $this->view(Service::fromEntity($result, $this->formServerApiAdapterService->getFormServerPublicUrl()), Response::HTTP_OK);
    } catch (\Exception $e) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }
  }

  /**
   * Retreive form Service schema
   * @Rest\Get("/{id}/form", name="form_service_api_get")
   *
   * @SWG\Response(
   *     response=200,
   *     description="Retreive service Form schma"
   * )
   *
   * @SWG\Response(
   *     response=404,
   *     description="Form schema not found"
   * )
   * @SWG\Tag(name="services")
   *
   * @param $id
   * @return View
   */
  public function getFormServiceAction($id)
  {
    try {
      $repository = $this->getDoctrine()->getRepository('AppBundle:Servizio');
      /** @var Servizio $service */
      $service = $repository->find($id);
      if ($service === null) {
        return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
      }

      $response = $this->formServerApiAdapterService->getForm($service->getFormIoId());

      if ($response['status'] == 'success') {
        return $this->view($response['form'], Response::HTTP_OK);
      } else {
        return $this->view(["Form not found"], Response::HTTP_NOT_FOUND);
      }
    } catch (\Exception $e) {
      return $this->view(["Service not found"], Response::HTTP_NOT_FOUND);
    }
  }


  /**
   * Create a Service
   * @Rest\Post(name="services_api_post")
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
   *     name="Service",
   *     in="body",
   *     type="json",
   *     description="The service to create",
   *     required=true,
   *     @SWG\Schema(
   *         type="object",
   *         ref=@Model(type=Service::class)
   *     )
   * )
   *
   * @SWG\Response(
   *     response=201,
   *     description="Create a Service"
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
   * @SWG\Tag(name="services")
   *
   * @param Request $request
   * @return View
   */
  public function postServiceAction(Request $request)
  {
    $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

    if (!$this->checkProtocolHandler($request)) {
      return $this->view(["Unknown protocol handler, allowed handlers are: " . implode(', ', $this->handlerList)], Response::HTTP_BAD_REQUEST);
    }

    $serviceDto = new Service();
    $form = $this->createForm('AppBundle\Form\ServizioFormType', $serviceDto);
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

    $em = $this->getDoctrine()->getManager();

    $category = $em->getRepository('AppBundle:Categoria')->findOneBy(['slug' => $serviceDto->getTopics()]);
    if ($category instanceof Categoria) {
      $serviceDto->setTopics($category);
    }

    $serviceGroup = $em->getRepository('AppBundle:ServiceGroup')->findOneBy(['slug' => $serviceDto->getServiceGroup()]);
    if ($serviceGroup instanceof ServiceGroup) {
      $serviceDto->setServiceGroup($serviceGroup);
    }

    $service = $serviceDto->toEntity();
    $service->setPraticaFCQN('\AppBundle\Entity\FormIO');
    $service->setPraticaFlowServiceName('ocsdc.form.flow.formio');

    // Imposto l'ente in base all'istanza
    $ente = $this->is->getCurrentInstance();
    $service->setEnte($ente);

    // Erogatore
    $erogatore = new Erogatore();
    $erogatore->setName('Erogatore di ' . $service->getName() . ' per ' . $ente->getName());
    $erogatore->addEnte($ente);
    $em->persist($erogatore);
    $service->activateForErogatore($erogatore);
    $em->persist($service);
    $em->flush();

    try {
      $em->persist($service);
      $em->flush();
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

    return $this->view(Service::fromEntity($service, $this->formServerApiAdapterService->getFormServerPublicUrl()), Response::HTTP_CREATED);
  }

  /**
   * Edit full Service
   * @Rest\Put("/{id}", name="services_api_put")
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
   *     name="Service",
   *     in="body",
   *     type="json",
   *     description="The service to create",
   *     required=true,
   *     @SWG\Schema(
   *         type="object",
   *         ref=@Model(type=Service::class)
   *     )
   * )
   *
   * @SWG\Response(
   *     response=200,
   *     description="Edit full Service"
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
   * @SWG\Tag(name="services")
   *
   * @param Request $request
   * @return View
   */
  public function putServiceAction($id, Request $request)
  {
    $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

    try {

      if (!$this->checkProtocolHandler($request)) {
        return $this->view(["Unknown protocol handler, allowed handlers are: " . implode(', ', $this->handlerList)], Response::HTTP_BAD_REQUEST);
      }

      $repository = $this->getDoctrine()->getRepository('AppBundle:Servizio');
      $service = $repository->find($id);

      if (!$service) {
        return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
      }
      //$serviceDto = Service::fromEntity($service);
      $serviceDto = new Service();
      $form = $this->createForm('AppBundle\Form\ServizioFormType', $serviceDto);
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

      $em = $this->getDoctrine()->getManager();
      $category = $em->getRepository('AppBundle:Categoria')->findOneBy(['slug' => $serviceDto->getTopics()]);
      if ($category instanceof Categoria) {
        $serviceDto->setTopics($category);
      }

      $serviceGroup = $em->getRepository('AppBundle:ServiceGroup')->findOneBy(['slug' => $serviceDto->getServiceGroup()]);
      if ($serviceGroup instanceof ServiceGroup) {
        $serviceDto->setServiceGroup($serviceGroup);
      }

      $service = $serviceDto->toEntity($service);


      $em->persist($service);
      $em->flush();
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
    /*} catch (\Exception $e) {
        return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }*/
  }

  /**
   * Patch a Service
   * @Rest\Patch("/{id}", name="services_api_patch")
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
   *     name="Service",
   *     in="body",
   *     type="json",
   *     description="The service to create",
   *     required=true,
   *     @SWG\Schema(
   *         type="object",
   *         ref=@Model(type=Service::class)
   *     )
   * )
   *
   * @SWG\Response(
   *     response=200,
   *     description="Patch a Service"
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
   * @SWG\Tag(name="services")
   *
   * @param $id
   * @param Request $request
   * @return View
   */
  public function patchServiceAction($id, Request $request)
  {
    $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

    try {

      if (!$this->checkProtocolHandler($request)) {
        return $this->view(["Unknown protocol handler, allowed handlers are: " . implode(', ', $this->handlerList)], Response::HTTP_BAD_REQUEST);
      }

      $em = $this->getDoctrine()->getManager();
      $repository = $this->getDoctrine()->getRepository('AppBundle:Servizio');
      /** @var Servizio $service */
      $service = $repository->find($id);

      if (!$service) {
        return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
      }
      $serviceDto = Service::fromEntity($service, $this->formServerApiAdapterService->getFormServerPublicUrl());
      $form = $this->createForm('AppBundle\Form\ServizioFormType', $serviceDto);
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

      $category = $em->getRepository('AppBundle:Categoria')->findOneBy(['slug' => $serviceDto->getTopics()]);
      if ($category instanceof Categoria) {
        $serviceDto->setTopics($category);
      }

      $serviceGroup = $em->getRepository('AppBundle:ServiceGroup')->findOneBy(['slug' => $serviceDto->getServiceGroup()]);
      if ($serviceGroup instanceof ServiceGroup) {
        $serviceDto->setServiceGroup($serviceGroup);
      }

      $service = $serviceDto->toEntity($service);

      $em->persist($service);
      $em->flush();
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
   * @Rest\Delete("/{id}", name="services_api_delete")
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
   * @SWG\Tag(name="services")
   *
   * @Method("DELETE")
   */
  public function deleteAction($id)
  {
    $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

    $service = $this->getDoctrine()->getRepository('AppBundle:Servizio')->find($id);
    if ($service) {
      // debated point: should we 404 on an unknown nickname?
      // or should we just return a nice 204 in all cases?
      // we're doing the latter
      $em = $this->getDoctrine()->getManager();
      $em->remove($service);
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
    $data = Service::normalizeData(json_decode($request->getContent(), true));
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

  private function checkProtocolHandler(Request $request)
  {
    if ($request->get('protocol_handler') && !in_array($request->get('protocol_handler'), $this->handlerList)) {
      return false;
    }
    return true;
  }
}
