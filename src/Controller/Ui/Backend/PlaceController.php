<?php

namespace App\Controller\Ui\Backend;

use App\Entity\Place;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class PlaceController
 * @Route("/admin/places")
 */
class PlaceController extends AbstractController
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
   * @Route("/", name="admin_place_index", methods={"GET"})
   *
   */
  public function indexPlacesAction(): Response
  {
    $items = $this->entityManager->getRepository('App\Entity\Place')->findBy([], ['name' => 'asc']);
    return $this->render('Admin/indexPlace.html.twig', [
      'user'  => $this->getUser(),
      'items' => $items
    ]);
  }


  /**
   * @Route("/new", name="admin_place_new", methods={"GET", "POST"})
   * @param Request $request
   * @return RedirectResponse|Response|null
   */
  public function newPlaceAction(Request $request)
  {
    $item = new Place();
    $form = $this->createForm('App\Form\Admin\PlaceType', $item);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $this->entityManager->persist($item);
      $this->entityManager->flush();

      $this->addFlash('feedback', $this->translator->trans('general.flash.created'));
      return $this->redirectToRoute('admin_place_edit', ['id' => $item->getId()]);
    }
    //dd($form->createView());
    return $this->render( 'Admin/editPlace.html.twig', [
      'user'  => $this->getUser(),
      'item' => $item,
      'form' => $form->createView(),
    ]);
  }


  /**
   * @Route("/{id}/edit", name="admin_place_edit", methods={"GET", "POST"})
   * @param Request $request
   * @param Place $item
   * @return Response|null
   */
  public function editPlaceAction(Request $request, Place $item): ?Response
  {
    $form = $this->createForm('App\Form\Admin\PlaceType', $item);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $this->entityManager->flush();
      return $this->redirectToRoute('admin_place_edit', ['id' => $item->getId()]);
    }

    return $this->render( 'Admin/editPlace.html.twig',
      [
        'user'  => $this->getUser(),
        'item' => $item,
        'form' => $form->createView()
      ]);
  }


  /**
   * @Route("/{id}/delete", name="admin_place_delete", methods={"GET", "POST"})
   */
  public function deletePlaceAction(Request $request, Place $item): RedirectResponse
  {
    try {
      $this->entityManager->remove($item);
      $this->entityManager->flush();
      $this->addFlash('feedback', $this->translator->trans('place.delete_success'));
      return $this->redirectToRoute('admin_place_index');

    } catch (ForeignKeyConstraintViolationException $exception) {
      $this->addFlash('warning', $this->translator->trans('place.delete_error'));
      return $this->redirectToRoute('admin_place_index');
    }
  }


}
