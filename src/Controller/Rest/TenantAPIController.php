<?php

namespace App\Controller\Rest;

use App\Dto\Tenant;
use App\Entity\Ente;
use App\Services\InstanceService;
use App\Utils\FormUtils;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class TenantAPIController
 * @property EntityManagerInterface em
 * @property InstanceService is
 * @package App\Controller
 * @Route("/tenants")
 */
class TenantAPIController extends AbstractFOSRestController
{
  const CURRENT_API_VERSION = '1.0';

  private EntityManagerInterface $em;
  private InstanceService $is;
  private LoggerInterface $logger;

  public function __construct(EntityManagerInterface $em, InstanceService $is, LoggerInterface $logger)
  {
    $this->em = $em;
    $this->is = $is;
    $this->logger = $logger;
  }

  /**
   * Get info Tenant
   * @Rest\Get("/info", name="tenants_api_info")
   *
   *
   * @OA\Response(
   *     response=200,
   *     description="Tenant info",
   *     @Model(type=Tenant::class, groups={"read"})
   * )
   *
   * @OA\Response(
   *     response=404,
   *     description="Tenant not found"
   * )
   *
   * @OA\Tag(name="tenants")
   */
  public function getTenantInfoAction(Request $request): View
  {
    try {
      $tenant = $this->is->getCurrentInstance();

      if (!$tenant) {
        return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
      }

      $tenantDto = Tenant::fromEntity($tenant);

      return $this->view($tenantDto, Response::HTTP_OK);
    } catch (NotFoundHttpException $e) {
      return $this->view([$e->getMessage()], Response::HTTP_NOT_FOUND);
    } catch (\Exception $e) {
      $data = [
        'type' => 'error',
        'title' => 'There was an error during get process',
        'description' => 'Contact technical support at support@opencontent.it',
      ];
      $this->logger->error(
        $e->getMessage(),
        ['request' => $request]
      );

      return $this->view($data, Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * Edit Tenant
   * @Rest\Put("/{identifier}", name="tenant_api_put")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The tenant to edit",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             ref=@Model(type=Tenant::class)
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=200,
   *     description="Edit full Tenant"
   * )
   *
   * @OA\Response(
   *     response=400,
   *     description="Bad request"
   * )
   * @OA\Response(
   *     response=401,
   *     description="Unauthorized"
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
   * @OA\Tag(name="tenants")
   *
   * @param $identifier
   * @param Request $request
   * @return View
   */
  public function putTenantAction($identifier, Request $request)
  {
    $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

    $tenant = $this->is->getCurrentInstance();
    if (!$tenant || ($tenant->getSlug() !== $identifier)) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $tenantDto = Tenant::fromEntity($tenant);
    $form = $this->createForm('App\Form\TenantType', $tenantDto);
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

    $tenant = $tenantDto->toEntity($tenant);

    try {
      $this->em->persist($tenant);
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
   * Patch a Tenant
   * @Rest\Patch("/{identifier}", name="tenant_api_patch")
   *
   * @Security(name="Bearer")
   *
   * @OA\RequestBody(
   *     description="The tenant to edit",
   *     required=true,
   *     @OA\MediaType(
   *         mediaType="application/json",
   *         @OA\Schema(
   *             type="object",
   *             ref=@Model(type=Tenant::class)
   *         )
   *     )
   * )
   *
   * @OA\Response(
   *     response=200,
   *     description="Patch tenant"
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
   * @OA\Tag(name="tenants")
   *
   * @param $identifier
   * @param Request $request
   * @return View
   */
  public function patchTenantAction($identifier, Request $request)
  {
    $this->denyAccessUnlessGranted(['ROLE_ADMIN']);

    $tenant = $this->is->getCurrentInstance();

    if (!$tenant || ($tenant->getSlug() !== $identifier)) {
      return $this->view(["Object not found"], Response::HTTP_NOT_FOUND);
    }

    $tenantDto = Tenant::fromEntity($tenant);
    $form = $this->createForm('App\Form\TenantType', $tenantDto);
    $this->processForm($request, $form);

    if ($form->isSubmitted() && !$form->isValid()) {
      $errors = FormUtils::getErrorsFromForm($form);
      $data = [
        'type' => 'patch_validation_error',
        'title' => 'There was a validation error',
        'errors' => $errors
      ];
      return $this->view($data, Response::HTTP_BAD_REQUEST);
    }

    $tenant = $tenantDto->toEntity($tenant);

    try {
      $this->em->persist($tenant);
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
   * @param Request $request
   * @param FormInterface $form
   */
  private function processForm(Request $request, FormInterface $form)
  {
    $data = Tenant::normalizeData(json_decode($request->getContent(), true));
    $clearMissing = $request->getMethod() != 'PATCH';
    $form->submit($data, $clearMissing);
  }
}
