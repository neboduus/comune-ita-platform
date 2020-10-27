<?php

namespace App\Controller\Admin;

use App\Entity\ServiceGroup;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ServiceGroupController
 * @Route("/admin/service-group")
 */
class ServiceGroupController extends AbstractController
{
  /**
   * Lists all Service Group entities.
   * @Route("", name="admin_service_group_index", methods={"GET"})
   */
  public function indexServiceGroupAction()
  {
    $em = $this->getDoctrine()->getManager();

    $items = $em->getRepository('App:ServiceGroup')->findAll();

    return $this->render( 'Admin/indexServiceGroup.html.twig', [
      'user'  => $this->getUser(),
      'items' => $items
    ]);
  }

  /**
   * Creates a new service group entity.
   * @Route("/new", name="admin_service_group_new", methods={"GET", "POST"})
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

      $this->addFlash('feedback', 'Gruppo di servizi creato con successo');
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
   * @Route("/{id}/edit", name="admin_service_group_edit", methods={"GET", "POST"})
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
   * @Route("/{id}/delete", name="admin_service_group_delete", methods={"GET", "POST", "DELETE"})
   */
  public function deleteServiceGroupAction(Request $request, ServiceGroup $serviceGroup)
  {
    try {
      $em = $this->getDoctrine()->getManager();
      $em->remove($serviceGroup);
      $em->flush();
      $this->addFlash('feedback', 'Gruppo di servizi eliminato correttamente');
      return $this->redirectToRoute('admin_service_group_index');

    } catch (ForeignKeyConstraintViolationException $exception) {
      $this->addFlash('warning', 'Impossibile eliminare il gruppo, ci sono dei servizi collegati.');
      return $this->redirectToRoute('admin_service_group_index');
    }
  }
}
