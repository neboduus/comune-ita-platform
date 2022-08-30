<?php

namespace App\Controller\Ui\Backend;

use App\Entity\GeographicArea;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Translation\TranslatorInterface;


/**
 * Class GeographicAreaController
 * @Route("/admin/geographic-areas")
 */
class GeographicAreaController extends AbstractController
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
   * @Route("/", name="admin_geographic_area_index")
   * @Method("GET")
   *
   */
  public function indexGeographicAreasAction()
  {

    $items = $this->entityManager->getRepository('App\Entity\GeographicArea')->findBy([], ['name' => 'asc']);

    return $this->render( 'Admin/indexGeographicArea.html.twig', [
      'user'  => $this->getUser(),
      'items' => $items
    ]);
  }

  /**
   * @Route("/new", name="admin_geographic_area_new")
   * @Method({"GET", "POST"})
   * @param Request $request
   * @return RedirectResponse|Response|null
   */
  public function newGeographicAreaAction(Request $request)
  {
    $item = new GeographicArea();
    $form = $this->createForm('App\Form\Admin\GeographicAreaType', $item);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $this->entityManager->persist($item);
      $this->entityManager->flush();

      $this->addFlash('feedback', $this->translator->trans('general.flash.created'));
      return $this->redirectToRoute('admin_geographic_area_edit', ['id' => $item->getId()]);
    }

    return $this->render( 'Admin/editGeographicArea.html.twig', [
      'user'  => $this->getUser(),
      'item' => $item,
      'form' => $form->createView(),
    ]);
  }

  /**
   * @Route("/{id}/edit", name="admin_geographic_area_edit")
   * @Method({"GET", "POST"})
   * @param Request $request
   * @param GeographicArea $item
   * @return Response|null
   */
  public function editGeographicAreaAction(Request $request, GeographicArea $item)
  {
    $form = $this->createForm('App\Form\Admin\GeographicAreaType', $item);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $this->entityManager->flush();
      return $this->redirectToRoute('admin_geographic_area_edit', ['id' => $item->getId()]);
    }

    return $this->render( 'Admin/editGeographicArea.html.twig',
      [
        'user'  => $this->getUser(),
        'item' => $item,
        'form' => $form->createView()
      ]);
  }

  /**
   * @Route("/{id}/delete", name="admin_geographic_area_delete")
   * @Method({"GET", "POST", "DELETE"})
   */
  public function deletePaymentGatewayAction(Request $request, GeographicArea $item)
  {
    try {
      $em = $this->getDoctrine()->getManager();
      $em->remove($item);
      $em->flush();
      $this->addFlash('feedback', $this->translator->trans('geographic_areas.delete_success'));
      return $this->redirectToRoute('admin_geographic_area_index');

    } catch (ForeignKeyConstraintViolationException $exception) {
      $this->addFlash('warning', $this->translator->trans('geographic_areas.delete_error'));
      return $this->redirectToRoute('admin_geographic_area_index');
    }
  }
}
