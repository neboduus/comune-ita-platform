<?php

namespace App\Controller\Ui\Backend;

use App\Entity\ServiceGroup;
use App\Entity\Servizio;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class ServiceGroupController
 * @Route("/admin/service-group")
 */
class ServiceGroupController extends Controller
{
  /**
   * @var TranslatorInterface
   */
  private $translator;

  /**
   * ServiceGroupController constructor.
   * @param TranslatorInterface $translator
   */
  public function __construct(TranslatorInterface $translator)
  {
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
}
