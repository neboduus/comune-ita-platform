<?php

namespace App\Controller\Ui\Backend;

use App\Entity\Categoria;
use App\Entity\ServiceGroup;
use App\Entity\Servizio;
use App\Services\Manager\CategoryManager;
use App\Services\Manager\PraticaManager;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Translation\TranslatorInterface;


/**
 * Class PaymentGatewayController
 * @Route("/admin/categories")
 */
class CategoryController extends Controller
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
   * @var CategoryManager
   */
  private $categoryManager;


  /**
   * @param EntityManagerInterface $entityManager
   * @param TranslatorInterface $translator
   * @param CategoryManager $categoryManager
   */
  public function __construct(EntityManagerInterface $entityManager, TranslatorInterface $translator, CategoryManager $categoryManager)
  {
    $this->entityManager = $entityManager;
    $this->translator = $translator;
    $this->categoryManager = $categoryManager;
  }


  /**
   * @Route("/", name="admin_category_index")
   * @Method("GET")
   *
   */
  public function indexCategoriesAction()
  {

    $items = $this->categoryManager->getCategoryTree();

    return $this->render('@App/Admin/indexCategory.html.twig', [
      'user' => $this->getUser(),
      'items' => $items
    ]);
  }

  /**
   * @Route("/new", name="admin_category_new")
   * @Method({"GET", "POST"})
   * @param Request $request
   * @return RedirectResponse|Response|null
   */
  public function newCategoryAction(Request $request)
  {
    $item = new Categoria();
    $form = $this->createForm('App\Form\Admin\CategoryType', $item);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $this->entityManager->persist($item);
      $this->entityManager->flush();

      $this->addFlash('feedback', $this->translator->trans('general.flash.created'));
      return $this->redirectToRoute('admin_category_edit', ['id' => $item->getId()]);
    }

    return $this->render('@App/Admin/editCategory.html.twig', [
      'user' => $this->getUser(),
      'item' => $item,
      'form' => $form->createView(),
    ]);
  }

  /**
   * @Route("/{id}/edit", name="admin_category_edit")
   * @Method({"GET", "POST"})
   * @param Request $request
   * @param Categoria $item
   * @return Response|null
   */
  public function editCategoryAction(Request $request, Categoria $item)
  {
    $form = $this->createForm('App\Form\Admin\CategoryType', $item);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $this->entityManager->flush();
      $this->addFlash('feedback', $this->translator->trans('general.flash.updated'));
      return $this->redirectToRoute('admin_category_edit', ['id' => $item->getId()]);
    }

    return $this->render('@App/Admin/editCategory.html.twig',
      [
        'user' => $this->getUser(),
        'item' => $item,
        'form' => $form->createView()
      ]);
  }

  /**
   * @Route("/{id}/delete", name="admin_category_delete")
   * @Method({"GET", "POST", "DELETE"})
   */
  public function deleteCategoryAction(Categoria $item)
  {
    try {
      $this->entityManager->remove($item);
      $this->entityManager->flush();
      $this->addFlash('feedback', $this->translator->trans('categories.delete_success'));
      return $this->redirectToRoute('admin_category_index');

    } catch (ForeignKeyConstraintViolationException $exception) {
      if ($item->getServices()->count() > 0) {
        $this->addFlash('warning', $this->translator->trans('categories.delete_service_error', ['%count%' => $item->getServices()->count()]));
      } elseif ($item->getServicesGroup()->count() > 0) {
        $this->addFlash('warning', $this->translator->trans('categories.delete_service_group_error', ['%count%' => $item->getServicesGroup()->count()]));
      } elseif ($item->getChildren()->count() > 0) {
        $this->addFlash('warning', $this->translator->trans('categories.delete_subcategories_error', ['%count%' => $item->getChildren()->count()]));
      } else {
        $this->addFlash('warning', $this->translator->trans('categories.service_remove_error', ['%count%' => $item->getChildren()->count()]));
      }
      return $this->redirectToRoute('admin_category_index');
    }
  }

  /**
   * Removes service from Category.
   * @Route("/{id}/remove-service", name="admin_category_remove_service")
   * @param Request $request
   * @param Servizio $service
   * @return RedirectResponse
   */
  public function removeServiceFromCategory(Servizio $service)
  {
    /** @var Categoria $category */
    $category = $service->getTopics();
    try {
      $service->setTopics(null);
      $this->entityManager->persist($service);
      $this->entityManager->flush();
      $this->addFlash('feedback', $this->translator->trans('categories.service_remove_success'));
      return $this->redirectToRoute('admin_category_edit', array('id' => $category->getId()));

    } catch (\Exception $exception) {
      $this->addFlash('warning', $this->translator->trans('categories.service_remove_error'));
      return $this->redirectToRoute('admin_category_edit', array('id' => $category->getId()));
    }
  }

  /**
   * Removes service from Category.
   * @Route("/{id}/remove-service-group", name="admin_category_remove_service_group")
   * @param Request $request
   * @param ServiceGroup $service
   * @return RedirectResponse
   */
  public function removeServiceGroupFromCategory(ServiceGroup $service)
  {
    /** @var Categoria $category */
    $category = $service->getTopics();
    try {
      $service->setTopics(null);
      $this->entityManager->persist($service);
      $this->entityManager->flush();
      $this->addFlash('feedback', $this->translator->trans('categories.service_remove_success'));
      return $this->redirectToRoute('admin_category_edit', array('id' => $category->getId()));

    } catch (\Exception $exception) {
      $this->addFlash('warning', $this->translator->trans('categories.service_remove_error'));
      return $this->redirectToRoute('admin_category_edit', array('id' => $category->getId()));
    }
  }
}
