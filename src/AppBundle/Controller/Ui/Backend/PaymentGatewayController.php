<?php

namespace AppBundle\Controller\Ui\Backend;

use AppBundle\Entity\PaymentGateway;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Webhook;
use AppBundle\Services\InstanceService;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Class PaymentGatewayController
 * @Route("/admin/payment-gateway")
 */
class PaymentGatewayController extends Controller
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
   * @Route("/", name="admin_payment_gateway_index")
   * @Method("GET")
   * @Security("is_granted('ROLE_SUPER_ADMIN')")
   */
  public function indexPaymentGatewaysAction()
  {

    $items = $this->entityManager->getRepository('AppBundle:PaymentGateway')->findAll();

    return $this->render( '@App/Admin/indexPaymentGateway.html.twig', [
      'user'  => $this->getUser(),
      'items' => $items
    ]);
  }

  /**
   * @Route("/new", name="admin_payment_gateway_new")
   * @Method({"GET", "POST"})
   * @Security("is_granted('ROLE_SUPER_ADMIN')")
   * @param Request $request
   * @return RedirectResponse|Response|null
   */
  public function newPaymentGatewayAction(Request $request)
  {
    $item = new PaymentGateway();
    $form = $this->createForm('AppBundle\Form\Admin\PaymentGatewayType', $item);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $item->setFcqn('AppBundle\Payment\Gateway\GenericExternalPay');
      $this->entityManager->persist($item);
      $this->entityManager->flush();

      $this->addFlash('feedback', 'Metodo di pagamento creato con successo');
      return $this->redirectToRoute('admin_payment_gateway_index');
    }

    return $this->render( '@App/Admin/editPaymentGateway.html.twig', [
      'user'  => $this->getUser(),
      'item' => $item,
      'form' => $form->createView(),
    ]);
  }

  /**
   * @Route("/{id}/edit", name="admin_payment_gateway_edit")
   * @Method({"GET", "POST"})
   * @Security("is_granted('ROLE_SUPER_ADMIN')")
   * @param Request $request
   * @param PaymentGateway $item
   * @return Response|null
   */
  public function editPaymentGatewayAction(Request $request, PaymentGateway $item)
  {
    $form = $this->createForm('AppBundle\Form\Admin\PaymentGatewayType', $item);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $this->entityManager->flush();
    }

    return $this->render( '@App/Admin/editPaymentGateway.html.twig',
      [
        'user'  => $this->getUser(),
        'item' => $item,
        'form' => $form->createView()
      ]);
  }

  /**
   * @Route("/{id}/delete", name="admin_payment_gateway_delete")
   * @Method({"GET", "POST", "DELETE"})
   * @Security("is_granted('ROLE_SUPER_ADMIN')")
   */
  public function deletePaymentGatewayAction(Request $request, PaymentGateway $item)
  {
    try {
      $em = $this->getDoctrine()->getManager();
      $em->remove($item);
      $em->flush();
      $this->addFlash('feedback', 'Metodo di pagamento eliminato correttamente');
      return $this->redirectToRoute('admin_payment_gateway_index');

    } catch (ForeignKeyConstraintViolationException $exception) {
      $this->addFlash('warning', 'Impossibile eliminare il Metodo di pagamento, ci sono dei servizi collegati.');
      return $this->redirectToRoute('admin_payment_gateway_index');
    }
  }
}
