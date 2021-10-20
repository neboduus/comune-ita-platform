<?php

namespace AppBundle\Controller\Ui\Backend;

use AppBundle\Entity\Recipient;
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
 * Class RecipientController
 * @Route("/admin/recipients")
 */
class RecipientController extends Controller
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
   * @Route("/", name="admin_recipient_index")
   * @Method("GET")
   *
   */
  public function indexRecipientsAction()
  {

    $items = $this->entityManager->getRepository('AppBundle:Recipient')->findBy([], ['name' => 'asc']);

    return $this->render( '@App/Admin/indexRecipient.html.twig', [
      'user'  => $this->getUser(),
      'items' => $items
    ]);
  }

  /**
   * @Route("/new", name="admin_recipient_new")
   * @Method({"GET", "POST"})
   * @param Request $request
   * @return RedirectResponse|Response|null
   */
  public function newRecipientAction(Request $request)
  {
    $item = new Recipient();
    $form = $this->createForm('AppBundle\Form\Admin\RecipientType', $item);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $this->entityManager->persist($item);
      $this->entityManager->flush();

      $this->addFlash('feedback', $this->translator->trans('general.flash.created'));
      return $this->redirectToRoute('admin_recipient_index');
    }

    return $this->render( '@App/Admin/editRecipient.html.twig', [
      'user'  => $this->getUser(),
      'item' => $item,
      'form' => $form->createView(),
    ]);
  }

  /**
   * @Route("/{id}/edit", name="admin_recipient_edit")
   * @Method({"GET", "POST"})
   * @param Request $request
   * @param Recipient $item
   * @return Response|null
   */
  public function editRecipientAction(Request $request, Recipient $item)
  {
    $form = $this->createForm('AppBundle\Form\Admin\RecipientType', $item);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $this->entityManager->flush();
    }

    return $this->render( '@App/Admin/editRecipient.html.twig',
      [
        'user'  => $this->getUser(),
        'item' => $item,
        'form' => $form->createView()
      ]);
  }

  /**
   * @Route("/{id}/delete", name="admin_recipient_delete")
   * @Method({"GET", "POST", "DELETE"})
   */
  public function deletePaymentGatewayAction(Request $request, Recipient $item)
  {
    try {
      $em = $this->getDoctrine()->getManager();
      $em->remove($item);
      $em->flush();
      $this->addFlash('feedback', $this->translator->trans('recipients.delete_success'));
      return $this->redirectToRoute('admin_recipient_index');

    } catch (ForeignKeyConstraintViolationException $exception) {
      $this->addFlash('warning', $this->translator->trans('recipients.delete_error'));
      return $this->redirectToRoute('admin_recipient_index');
    }
  }
}
