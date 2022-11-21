<?php

namespace App\Controller\Ui\Backend;

use App\Entity\Servizio;
use App\Entity\UserGroup;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class UserGroupController
 * @Route("/admin/user-groups")
 */
class UserGroupController extends AbstractController
{
  /**
   * @var EntityManagerInterface
   */
  private $entityManager;
  /**
   * @var TranslatorInterface
   */
  private $translator;

  /**
   * @param EntityManagerInterface $entityManager
   * @param TranslatorInterface $translator
   */
  public function __construct(EntityManagerInterface $entityManager, TranslatorInterface $translator)
  {
    $this->entityManager = $entityManager;
    $this->translator = $translator;
  }


  /**
   * @Route("/", name="admin_user_group_index")
   * @Method("GET")
   *
   */
  public function indexUserGroupsAction(): Response
  {
    $items = $this->entityManager->getRepository('App\Entity\UserGroup')->findBy([], ['name' => 'asc']);

    // TODO: verifica variabili da passare al template
    return $this->render('Admin/indexUserGroup.html.twig', [
      'user'  => $this->getUser(),
      'items' => $items
    ]);
  }


  /**
   * @Route("/new", name="admin_user_group_new")
   * @Method({"GET", "POST"})
   * @param Request $request
   * @return RedirectResponse|Response|null
   */
  public function newUserGroupAction(Request $request)
  {
    $item = new UserGroup();
    $form = $this->createForm('App\Form\Admin\UserGroupType', $item);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $this->entityManager->persist($item);
      $this->entityManager->flush();

      $this->addFlash('feedback', $this->translator->trans('general.flash.created'));
      return $this->redirectToRoute('admin_user_group_edit', ['id' => $item->getId()]);
    }

    return $this->render( 'Admin/editUserGroup.html.twig', [
      'user'  => $this->getUser(),
      'item' => $item,
      'form' => $form->createView(),
    ]);
  }


  /**
   * @Route("/{id}/edit", name="admin_user_group_edit")
   * @Method({"GET", "POST"})
   * @param Request $request
   * @param UserGroup $item
   * @return Response|null
   */
  public function editUserGroupAction(Request $request, UserGroup $item): ?Response
  {
    $form = $this->createForm('App\Form\Admin\UserGroupType', $item);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $this->entityManager->flush();
      return $this->redirectToRoute('admin_user_group_edit', ['id' => $item->getId()]);
    }

    return $this->render( 'Admin/editUserGroup.html.twig',
      [
        'user'  => $this->getUser(),
        'item' => $item,
        'form' => $form->createView()
      ]);
  }


  /**
   * @Route("/{id}/delete", name="admin_user_group_delete")
   * @Method({"GET", "POST", "DELETE"})
   */
  public function deletePaymentGatewayAction(Request $request, UserGroup $item): RedirectResponse
  {
    try {
      $em = $this->getDoctrine()->getManager();
      $em->remove($item);
      $em->flush();
      $this->addFlash('feedback', $this->translator->trans('user_groups.delete_success'));
      return $this->redirectToRoute('admin_user_group_index');

    } catch (ForeignKeyConstraintViolationException $exception) {
      $this->addFlash('warning', $this->translator->trans('user_groups.delete_error'));
      return $this->redirectToRoute('admin_user_group_index');
    }
  }

  /**
   * Removes a Service from a User Group.
   * @Route("/{id}/remove_service", name="admin_remove_service_group")
   * @param Request $request
   * @param Servizio $service
   * @return RedirectResponse
   */
  public function removeServiceFromGroup(Request $request, Servizio $service): RedirectResponse
  {
    $userGroup = $service->getUserGroups();
    try {
      $em = $this->getDoctrine()->getManager();
      $service->setServiceGroup(null);
      $em->persist($service);
      $em->flush();
      $this->addFlash('feedback', $this->translator->trans('gruppo_di_servizi.servizio_rimosso'));
      return $this->redirectToRoute('admin_user_group_edit', array('id' => $userGroup->getId()));

    } catch (\Exception $exception) {
      $this->addFlash('warning', $this->translator->trans('gruppo_di_servizi.errore_rimozione'));
      return $this->redirectToRoute('admin_user_group_edit', array('id' => $userGroup->getId()));
    }
  }

}
