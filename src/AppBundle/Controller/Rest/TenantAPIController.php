<?php

namespace AppBundle\Controller\Rest;

use AppBundle\Dto\Service;
use AppBundle\Dto\Tenant;
use AppBundle\Services\InstanceService;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\Form\FormInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class TenantAPIController
 * @property EntityManagerInterface em
 * @property InstanceService is
 * @package AppBundle\Controller
 * @Route("/tenants")
 */
class TenantAPIController extends AbstractFOSRestController
{
  const CURRENT_API_VERSION = '1.0';

  private $em;
  private $is;
  /**
   * @var TranslatorInterface $translator
   */
  private $translator;

  /** @var LoggerInterface */
  private $logger;

  public function __construct(TranslatorInterface $translator, EntityManagerInterface $em, InstanceService $is, LoggerInterface $logger)
  {
    $this->translator = $translator;
    $this->em = $em;
    $this->is = $is;
    $this->logger = $logger;
  }

  /**
   * Edit Tenant
   * @Rest\Put("/{identifier}", name="tenant_api_put")
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
   *     name="Tenant",
   *     in="body",
   *     type="json",
   *     description="The tenant to edit",
   *     required=true,
   *     @SWG\Schema(
   *         type="object",
   *         ref=@Model(type=Tenant::class)
   *     )
   * )
   *
   * @SWG\Response(
   *     response=200,
   *     description="Edit full Tenant"
   * )
   *
   * @SWG\Response(
   *     response=400,
   *     description="Bad request"
   * )
   * @SWG\Response(
   *     response=401,
   *     description="Unauthorized"
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
   * @SWG\Tag(name="Tenants")
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
    $form = $this->createForm('AppBundle\Form\TenantType', $tenantDto);
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
   * @SWG\Parameter(
   *     name="Authorization",
   *     in="header",
   *     description="The authentication Bearer",
   *     required=true,
   *     type="string"
   * )
   *
   * @SWG\Parameter(
   *     name="Tenant",
   *     in="body",
   *     type="json",
   *     description="The tenant to edit",
   *     required=true,
   *     @SWG\Schema(
   *         type="object",
   *         ref=@Model(type=Tenant::class)
   *     )
   * )
   *
   * @SWG\Response(
   *     response=200,
   *     description="Patch tenant"
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
   * @SWG\Tag(name="Tenants")
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
    $form = $this->createForm('AppBundle\Form\TenantType', $tenantDto);
    $this->processForm($request, $form);

    if ($form->isSubmitted() && !$form->isValid()) {
      $errors = $this->getErrorsFromForm($form);
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
