<?php

namespace AppBundle\Controller\Ui\Backend;

use AppBundle\Entity\Categoria;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;


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
   * @param EntityManagerInterface $entityManager
   */
  public function __construct(EntityManagerInterface $entityManager)
  {
    $this->entityManager = $entityManager;
  }


  /**
   * @Route("/", name="admin_category_index")
   * @Method("GET")
   *
   */
  public function indexCategoriesAction()
  {

    $items = $this->entityManager->getRepository('AppBundle:Categoria')->findBy([], ['name' => 'asc']);

    return $this->render( '@App/Admin/indexCategory.html.twig', [
      'user'  => $this->getUser(),
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
    $form = $this->createForm('AppBundle\Form\Admin\CategoryType', $item);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $this->entityManager->persist($item);
      $this->entityManager->flush();

      $this->addFlash('feedback', 'general.flash.created');
      return $this->redirectToRoute('admin_category_index');
    }

    return $this->render( '@App/Admin/editCategory.html.twig', [
      'user'  => $this->getUser(),
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
    $form = $this->createForm('AppBundle\Form\Admin\CategoryType', $item);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $this->entityManager->flush();
    }

    return $this->render( '@App/Admin/editCategory.html.twig',
      [
        'user'  => $this->getUser(),
        'item' => $item,
        'form' => $form->createView()
      ]);
  }

  /**
   * @Route("/{id}/delete", name="admin_category_delete")
   * @Method({"GET", "POST", "DELETE"})
   */
  public function deletePaymentGatewayAction(Request $request, Categoria $item)
  {
    try {
      $em = $this->getDoctrine()->getManager();
      $em->remove($item);
      $em->flush();
      $this->addFlash('feedback', 'Metodo di pagamento eliminato correttamente');
      return $this->redirectToRoute('admin_category_index');

    } catch (ForeignKeyConstraintViolationException $exception) {
      $this->addFlash('warning', 'Impossibile eliminare il Metodo di pagamento, ci sono dei servizi collegati.');
      return $this->redirectToRoute('admin_category_index');
    }
  }
}
