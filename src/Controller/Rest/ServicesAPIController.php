<?php

namespace App\Controller\Rest;

use App\Entity\Categoria;
use App\Entity\ServiceGroup;
use App\Entity\Servizio;
use App\Dto\Service;
use App\Services\FormServerApiAdapterService;
use App\Services\InstanceService;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\Form\FormInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;

/**
 * Class ServicesAPIController
 * @property EntityManagerInterface em
 * @property InstanceService is
 * @package App\Controller
 * @Route("/services")
 */
class ServicesAPIController extends AbstractFOSRestController
{
  const CURRENT_API_VERSION = '1.0';

  /** @var EntityManagerInterface  */
  private $em;

  /** @var InstanceService  */
  private $is;

  /** @var LoggerInterface */
  private $logger;

  /** @var FormServerApiAdapterService */
  private $formServerApiAdapterService;


  public function __construct(EntityManagerInterface $em, InstanceService $is, LoggerInterface $logger, FormServerApiAdapterService $formServerApiAdapterService)
  {
    $this->em = $em;
    $this->is = $is;
    $this->logger = $logger;
    $this->formServerApiAdapterService = $formServerApiAdapterService;
  }


  /**
   * List all Services
   * @Rest\Get("", name="services_api_list")
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
  public function getServicesAction()
  {
    $result = [];
    $services = $this->getDoctrine()->getRepository('App:Servizio')->findAll();
    foreach ($services as $s) {
      $result []= Service::fromEntity($s, $this->formServerApiAdapterService->getFormServerPublicUrl());
    }

    return $this->view($result, Response::HTTP_OK);
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
   * @return \FOS\RestBundle\View\View
   */
  public function getServiceAction($id)
  {
    try {
      $repository = $this->getDoctrine()->getRepository('App:Servizio');
      $result = $repository->find($id);
      if ($result === null) {
        return $this->view("Object not found", Response::HTTP_NOT_FOUND);
      }

      return $this->view(Service::fromEntity($result, $this->formServerApiAdapterService->getFormServerPublicUrl()), Response::HTTP_OK);
    } catch (\Exception $e) {
      return $this->view("Object not found", Response::HTTP_NOT_FOUND);
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
   * @return \FOS\RestBundle\View\View
   */
  public function getFormServiceAction($id)
  {
    try {
      $repository = $this->getDoctrine()->getRepository('App:Servizio');
      /** @var Servizio $service */
      $service = $repository->find($id);
      if ($service === null) {
        return $this->view("Object not found", Response::HTTP_NOT_FOUND);
      }

      $response = $this->formServerApiAdapterService->getForm($service->getFormIoId());

      if ($response['status'] == 'success') {
        return $this->view($response['form'], Response::HTTP_OK);
      } else {
        return $this->view("Form not found", Response::HTTP_NOT_FOUND);
      }
    } catch (\Exception $e) {
      return $this->view("Service not found", Response::HTTP_NOT_FOUND);
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
   * @SWG\Tag(name="services")
   *
   * @param Request $request
   * @return \FOS\RestBundle\View\View
   */
  public function postServiceAction(Request $request)
  {
    $serviceDto = new Service();
    $form = $this->createForm('App\Form\ServizioFormType', $serviceDto);
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

    $category = $em->getRepository('App:Categoria')->findOneBy(['slug' => $serviceDto->getTopics()]);
    if ($category instanceof Categoria) {
      $serviceDto->setTopics($category);
    }

    $serviceGroup = $em->getRepository('App:ServiceGroup')->findOneBy(['slug' => $serviceDto->getServiceGroup()]);
    if ($serviceGroup instanceof ServiceGroup) {
      $serviceDto->setServiceGroup($serviceGroup);
    }

    $service = $serviceDto->toEntity();
    $service->setPraticaFCQN('\App\Entity\FormIO');
    $service->setPraticaFlowServiceName('ocsdc.form.flow.formio');

    // Imposto l'ente in base all'istanza
    $service->setEnte($this->is->getCurrentInstance());

    try {
      $em->persist($service);
      $em->flush();
    } catch (\Exception $e) {

      $data = [
        'type' => 'error',
        'title' => 'There was an error during save process',
        'description' => $e->getMessage()
      ];
      $this->logger->error(
        $e->getMessage(),
        ['request' => $request]
      );
      return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return $this->view(Service::fromEntity($service), Response::HTTP_CREATED);
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
   *     response=404,
   *     description="Not found"
   * )
   * @SWG\Tag(name="services")
   *
   * @param Request $request
   * @return \FOS\RestBundle\View\View
   */
  public function putServiceAction($id, Request $request)
  {
    /*try {*/
    $repository = $this->getDoctrine()->getRepository('App:Servizio');
    $service = $repository->find($id);

    if (!$service) {
      return $this->view("Object not found", Response::HTTP_NOT_FOUND);
    }
    //$serviceDto = Service::fromEntity($service);
    $serviceDto = new Service();
    $form = $this->createForm('App\Form\ServizioFormType', $serviceDto);
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
    $category = $em->getRepository('App:Categoria')->findOneBy(['slug' => $serviceDto->getTopics()]);
    if ($category instanceof Categoria) {
      $serviceDto->setTopics($category);
    }

    $serviceGroup = $em->getRepository('App:ServiceGroup')->findOneBy(['slug' => $serviceDto->getServiceGroup()]);
    if ($serviceGroup instanceof ServiceGroup) {
      $serviceDto->setServiceGroup($serviceGroup);
    }

    $service = $serviceDto->toEntity($service);

    try {
      $em->persist($service);
      $em->flush();
    } catch (\Exception $e) {

      $data = [
        'type' => 'error',
        'title' => $e->getMessage()
      ];
      $this->logger->error(
        $e->getMessage(),
        ['request' => $request]
      );
      return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return $this->view("Object Modified Successfully", Response::HTTP_OK);
    /*} catch (\Exception $e) {
        return $this->view("Object not found", Response::HTTP_NOT_FOUND);
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
   *     response=404,
   *     description="Not found"
   * )
   * @SWG\Tag(name="services")
   *
   * @param Request $request
   * @return \FOS\RestBundle\View\View
   */
  public function patchServiceAction($id, Request $request)
  {
    $em = $this->getDoctrine()->getManager();
    $repository = $this->getDoctrine()->getRepository('App:Servizio');
    $service = $repository->find($id);

    if (!$service) {
      return $this->view("Object not found", Response::HTTP_NOT_FOUND);
    }
    $serviceDto = Service::fromEntity($service);
    $form = $this->createForm('App\Form\ServizioFormType', $serviceDto);
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

    $category = $em->getRepository('App:Categoria')->findOneBy(['slug' => $serviceDto->getTopics()]);
    if ($category instanceof Categoria) {
      $serviceDto->setTopics($category);
    }

    $serviceGroup = $em->getRepository('App:ServiceGroup')->findOneBy(['slug' => $serviceDto->getServiceGroup()]);
    if ($serviceGroup instanceof ServiceGroup) {
      $serviceDto->setServiceGroup($serviceGroup);
    }

    $service = $serviceDto->toEntity($service);

    try {
      $em->persist($service);
      $em->flush();
    } catch (\Exception $e) {

      $data = [
        'type' => 'error',
        'title' => 'There was an error during save process'
      ];
      $this->logger->error(
        $e->getMessage(),
        ['request' => $request]
      );
      return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return $this->view("Object Patched Successfully", Response::HTTP_OK);
  }

  /**
   * Delete a Service
   * @Rest\Delete("/{id}", name="services_api_delete")
   *
   * @SWG\Response(
   *     response=204,
   *     description="The resource was deleted successfully."
   * )
   * @SWG\Tag(name="services")
   */
  public function deleteAction($id)
  {
    $service = $this->getDoctrine()->getRepository('App:Servizio')->find($id);
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
}
