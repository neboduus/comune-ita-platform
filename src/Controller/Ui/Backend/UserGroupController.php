<?php

namespace App\Controller\Ui\Backend;

use App\Entity\UserGroup;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Routing\Annotation\Route;

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
   * @Route("/", name="admin_user_group_index", methods={"GET"})
   *
   */
  public function indexUserGroupsAction(): Response
  {
    $items = $this->entityManager->getRepository('App\Entity\UserGroup')->findBy([], ['name' => 'asc']);
    return $this->render('Admin/indexUserGroup.html.twig', [
      'user'  => $this->getUser(),
      'items' => $items
    ]);
  }


  /**
   * @Route("/new", name="admin_user_group_new", methods={"GET", "POST"})
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
   * @Route("/{id}/edit", name="admin_user_group_edit", methods={"GET", "POST"})
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
   * @Route("/{id}/delete", name="admin_user_group_delete", methods={"GET", "POST"})
   */
  public function deleteUserGroupAction(Request $request, UserGroup $item): RedirectResponse
  {
    try {
      $this->entityManager->remove($item);
      $this->entityManager->flush();
      $this->addFlash('feedback', $this->translator->trans('user_group.delete_success'));
      return $this->redirectToRoute('admin_user_group_index');

    } catch (ForeignKeyConstraintViolationException $exception) {
      $this->addFlash('warning', $this->translator->trans('user_group.delete_error'));
      return $this->redirectToRoute('admin_user_group_index');
    }
  }

}
