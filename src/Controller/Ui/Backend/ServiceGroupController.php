<?php

namespace App\Controller\Ui\Backend;

use App\Entity\ServiceGroup;
use App\Entity\Servizio;
use App\Model\PublicFile;
use App\Services\FileService\ServiceAttachmentsFileService;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FileNotFoundException;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class ServiceGroupController
 * @Route("/admin/service-group")
 */
class ServiceGroupController extends AbstractController
{
  /**
   * @var TranslatorInterface
   */
  private $translator;

  /**
   * @var EntityManagerInterface
   */
  private $entityManager;

  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * ServiceGroupController constructor.
   * @param EntityManagerInterface $entityManager
   * @param LoggerInterface $logger
   * @param TranslatorInterface $translator
   */
  public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger,TranslatorInterface $translator)
  {
    $this->entityManager = $entityManager;
    $this->logger = $logger;
    $this->translator = $translator;
  }

  /**
   * Lists all Service Group entities.
   * @Route("", name="admin_service_group_index")
   * @Method("GET")
   */
  public function indexServiceGroupAction()
  {
    $em = $this->getDoctrine()->getManager();

    $items = $em->getRepository('App\Entity\ServiceGroup')->findAll();

    return $this->render( 'Admin/indexServiceGroup.html.twig', [
      'user'  => $this->getUser(),
      'items' => $items
    ]);
  }

  /**
   * Creates a new service group entity.
   * @Route("/new", name="admin_service_group_new")
   * @Method({"GET", "POST"})
   */
  public function newServiceGroupAction(Request $request)
  {
    $serviceGroup = new ServiceGroup();
    $form = $this->createForm('App\Form\Admin\ServiceGroup\ServiceGroupType', $serviceGroup);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $em = $this->getDoctrine()->getManager();
      $em->persist($serviceGroup);
      $em->flush();

      $this->addFlash('feedback', $this->translator->trans('backoffice.integration.subscriptions.create_service_group_success'));
      return $this->redirectToRoute('admin_service_group_index');
    }

    return $this->render( 'Admin/editServiceGroup.html.twig', [
      'user'  => $this->getUser(),
      'item' => $serviceGroup,
      'form' => $form->createView()
    ]);
  }

  /**
   * Displays a form to edit an existing service group entity.
   * @Route("/{id}/edit", name="admin_service_group_edit")
   * @Method({"GET", "POST"})
   */
  public function editServiceGroupAction(Request $request, ServiceGroup $serviceGroup)
  {
    $form = $this->createForm('App\Form\Admin\ServiceGroup\ServiceGroupType', $serviceGroup);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $this->getDoctrine()->getManager()->flush();

      return $this->redirectToRoute('admin_service_group_edit', array('id' => $serviceGroup->getId()));
    }

    return $this->render( 'Admin/editServiceGroup.html.twig', [
      'user'  => $this->getUser(),
      'item' => $serviceGroup,
      'form' => $form->createView()
    ]);
  }

  /**
   * Deletes a service group entity.
   * @Route("/{id}/delete", name="admin_service_group_delete")
   * @Method({"GET", "POST", "DELETE"})
   */
  public function deleteServiceGroupAction(Request $request, ServiceGroup $serviceGroup)
  {
    try {
      $em = $this->getDoctrine()->getManager();
      $em->remove($serviceGroup);
      $em->flush();
      $this->addFlash('feedback', $this->translator->trans('backoffice.integration.subscriptions.delete_service_group_success'));
      return $this->redirectToRoute('admin_service_group_index');
    } catch (ForeignKeyConstraintViolationException $exception) {
      $this->addFlash('warning', $this->translator->trans('backoffice.integration.subscriptions.delete_service_group_error'));
      return $this->redirectToRoute('admin_service_group_index');
    }
  }

  /**
   * Removes a Service from a Service Group.
   * @Route("/{id}/remove_group", name="admin_service_remove_group")
   * @param Request $request
   * @param Servizio $service
   * @return RedirectResponse
   */
  public function removeServiceFromGroup(Request $request, Servizio $service)
  {
    $serviceGroup = $service->getServiceGroup();
    try {
      $em = $this->getDoctrine()->getManager();
      $service->setServiceGroup(null);
      $em->persist($service);
      $em->flush();
      $this->addFlash('feedback', $this->translator->trans('gruppo_di_servizi.servizio_rimosso'));
      return $this->redirectToRoute('admin_service_group_edit', array('id' => $serviceGroup->getId()));

    } catch (\Exception $exception) {
      $this->addFlash('warning', $this->translator->trans('gruppo_di_servizi.errore_rimozione'));
      return $this->redirectToRoute('admin_service_group_edit', array('id' => $serviceGroup->getId()));
    }
  }

  /**
   * @Route("/{id}/attachments/{attachmentType}/{filename}", name="admin_delete_group_attachment", methods={"DELETE"})
   * @ParamConverter("serviceGroup", class="App\Entity\ServiceGroup")
   * @param Request $request
   * @param ServiceGroup $serviceGroup
   * @param string $attachmentType
   * @param string $filename
   * @param ServiceAttachmentsFileService $fileService
   * @return JsonResponse
   */
  public function deletePublicAttachmentAction(Request $request, ServiceGroup $serviceGroup, string $attachmentType, string $filename, ServiceAttachmentsFileService $fileService): JsonResponse
  {
    if (!in_array($attachmentType, [PublicFile::CONDITIONS_TYPE, PublicFile::COSTS_TYPE])) {
      $this->logger->error("Invalid type $attachmentType");
      return new JsonResponse(["Invalid type: $attachmentType is not supported"], Response::HTTP_BAD_REQUEST);
    }

    if ($attachmentType === PublicFile::CONDITIONS_TYPE) {
      $attachment = $serviceGroup->getConditionAttachmentByName($filename);
    } elseif ($attachmentType === PublicFile::COSTS_TYPE) {
      $attachment = $serviceGroup->getCostAttachmentByName($filename);
    } else {
      $attachment = null;
    }

    if (!$attachment) {
      return new JsonResponse(["Attachment $filename does not exists"], Response::HTTP_NOT_FOUND);
    }

    try {
      $fileService->deleteFilename($attachment->getName(), $serviceGroup, $attachment->getType());
    } catch (FileNotFoundException $e) {
      $this->logger->error("Unable to delete $filename: file not found");
    }

    if ($attachmentType === PublicFile::CONDITIONS_TYPE) {
      $serviceGroup->removeConditionsAttachment($attachment);
    } elseif ($attachmentType === PublicFile::COSTS_TYPE) {
      $serviceGroup->removeCostsAttachment($attachment);
    }

    $this->entityManager->persist($serviceGroup);
    $this->entityManager->flush();

    return new JsonResponse(["$filename deleted successfully"], Response::HTTP_OK);
  }
}
