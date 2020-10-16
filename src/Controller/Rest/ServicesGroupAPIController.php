<?php

namespace App\Controller\Rest;

use App\Entity\ServiceGroup;
use App\Services\InstanceService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\Form\FormInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Ramsey\Uuid\Uuid;

/**
 * Class ServicesAPIController
 * @property EntityManagerInterface em
 * @property InstanceService is
 * @package App\Controller
 * @Route("/services-groups")
 */
class ServicesGroupAPIController extends AbstractFOSRestController
{

  public function __construct(EntityManagerInterface $em, InstanceService $is)
  {
    $this->em = $em;
    $this->is = $is;
  }


  /**
   * List all Services groups
   * @Rest\Get("", name="services_groups_api_list")
   *
   * @SWG\Response(
   *     response=200,
   *     description="Retrieve list of services groups",
   *     @SWG\Schema(
   *         type="array",
   *         @SWG\Items(ref=@Model(type=ServiceGroup::class))
   *     )
   * )
   * @SWG\Tag(name="services-groups")
   */
  public function getServicesGroupsAction()
  {
    $result = [];
    $services = $this->getDoctrine()->getRepository('App:ServiceGroup')->findAll();
    foreach ($services as $s) {
      $result []= $s;
    }

    return $this->view($result, Response::HTTP_OK);
  }

  /**
   * Retreive a Service group by id or slug
   * @Rest\Get("/{id}", name="service_group_api_get")
   *
   * @SWG\Response(
   *     response=200,
   *     description="Retreive a Service group by id or slug",
   *     @Model(type=ServiceGroup::class)
   * )
   *
   * @SWG\Response(
   *     response=404,
   *     description="Not found"
   * )
   * @SWG\Tag(name="services-groups")
   *
   * @param $id
   * @return View
   */
  public function getServiceAction($id)
  {
    try {
      $repository = $this->getDoctrine()->getRepository('App:ServiceGroup');

      if (Uuid::isValid($id) ) {
        $result = $repository->find($id);
      } else {
        $result = $repository->findOneBy(['slug' => $id]);
      }

      if ($result === null) {
        return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
      }

      return $this->view($result, Response::HTTP_OK);
    } catch (\Exception $e) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }
  }


  /**
   * Create a Service Group
   * @Rest\Post(name="services_group_api_post")
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
   *     name="Service Group",
   *     in="body",
   *     type="json",
   *     description="The service group to create",
   *     required=true,
   *     @SWG\Schema(
   *         type="object",
   *         ref=@Model(type=ServiceGroup::class)
   *     )
   * )
   *
   * @SWG\Response(
   *     response=201,
   *     description="Create a Service group"
   * )
   *
   * @SWG\Response(
   *     response=400,
   *     description="Bad request"
   * )
   * @SWG\Tag(name="services-groups")
   *
   * @param Request $request
   * @return View
   */
  public function postServiceAction(Request $request)
  {
    $serviceGroup = new ServiceGroup();
    $form = $this->createForm('App\Form\Admin\ServiceGroup\ServiceGroupType', $serviceGroup);
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

    try {
      $em->persist($serviceGroup);
      $em->flush();
    } catch (UniqueConstraintViolationException $e) {
      $data = [
        'type' => 'error',
        'title' => 'There was an error during save process',
        'description' => 'Duplicate object'
      ];
      $this->get('logger')->error(
        $e->getMessage(),
        ['request' => $request]
      );
      return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
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

    return $this->view($serviceGroup, Response::HTTP_CREATED);
  }

  /**
   * Edit full Service group
   * @Rest\Put("/{id}", name="service_group_api_put")
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
   *     name="Service group",
   *     in="body",
   *     type="json",
   *     description="The service group to create",
   *     required=true,
   *     @SWG\Schema(
   *         type="object",
   *         ref=@Model(type=ServiceGroup::class)
   *     )
   * )
   *
   * @SWG\Response(
   *     response=200,
   *     description="Edit full Service group"
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
   * @SWG\Tag(name="services-groups")
   *
   * @param Request $request
   * @return View
   */
  public function putServiceAction($id, Request $request)
  {
    $repository = $this->getDoctrine()->getRepository('App:ServiceGroup');
    $serviceGroup = $repository->find($id);

    if (!$serviceGroup) {
      return $this->view("Object not found", Response::HTTP_NOT_FOUND);
    }

    $form = $this->createForm('App\Form\Admin\ServiceGroup\ServiceGroupType', $serviceGroup);
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

    try {
      $em->persist($serviceGroup);
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
   * Patch a Service group
   * @Rest\Patch("/{id}", name="service_group_api_patch")
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
   *     name="Service group",
   *     in="body",
   *     type="json",
   *     description="The service group to create",
   *     required=true,
   *     @SWG\Schema(
   *         type="object",
   *         ref=@Model(type=ServiceGroup::class)
   *     )
   * )
   *
   * @SWG\Response(
   *     response=200,
   *     description="Patch a Service group"
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
   * @SWG\Tag(name="services-groups")
   *
   * @param Request $request
   * @return View
   */
  public function patchServiceAction($id, Request $request)
  {
    $em = $this->getDoctrine()->getManager();
    $repository = $this->getDoctrine()->getRepository('App:ServiceGroup');
    $serviceGroup = $repository->find($id);

    if (!$serviceGroup) {
      return $this->view("Object not found", Response::HTTP_NOT_FOUND);
    }

    $form = $this->createForm('App\Form\Admin\ServiceGroup\ServiceGroupType', $serviceGroup);
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
      $em->persist($serviceGroup);
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
   * Delete a Service group
   * @Rest\Delete("/{id}", name="service_gropu_api_delete")
   *
   * @SWG\Response(
   *     response=204,
   *     description="The resource was deleted successfully."
   * )
   * @SWG\Tag(name="services-groups")
   *
   * @Method("DELETE")
   */
  public function deleteAction($id)
  {
    $service = $this->getDoctrine()->getRepository('App:ServiceGroup')->find($id);
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
}
