<?php

namespace AppBundle\Controller;

use AppBundle\Entity\SubscriptionService;
use AppBundle\Model\SubscriptionPayment;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


/**
 * Class SubscriptionServicesController
 */
class SubscriptionServicesController extends Controller
{
  /**
   * Lists all SubscriptionService entities.
   * @Template()
   * @Route("/operatori/subscription-service", name="operatori_subscription-service_index")
   * @Method("GET")
   */
  public function indexSubscriptionServiceAction()
  {
    $statuses = [
      SubscriptionService::STATUS_WAITING => 'Pending',
      SubscriptionService::STATUS_ACTIVE => 'Attivo',
      SubscriptionService::STATUS_UNACTIVE => 'Inattivo'
    ];
    $em = $this->getDoctrine()->getManager();
    $items = $em->getRepository('AppBundle:SubscriptionService')->findAll();

    return array(
      'items' => $items,
      'statuses' => $statuses
    );
  }

  /**
   * Creates a new SubscriptionService entity.
   * @Template()
   * @Route("/operatori/subscription-service/new", name="operatori_subscription-service_new")
   * @Method({"GET", "POST"})
   * @param Request $request the request
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function newSubscriptionServiceAction(Request $request)
  {
    $subscriptionService = new SubscriptionService();
    $form = $this->createForm('AppBundle\Form\SubscriptionServiceType', $subscriptionService);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $em = $this->getDoctrine()->getManager();

      try {
        $em->persist($subscriptionService);
        $em->flush();

        $this->addFlash('feedback', 'Servizio a sottoscrizione creato correttamente');
        return $this->redirectToRoute('operatori_subscription-service_index');
      } catch (\Exception $exception) {
        $this->addFlash('error', 'Creazione fallita. Verifica che non esista già un servizio con questo codice');
      }
    }

    return array(
      'subscriptionService' => $subscriptionService,
      'form' => $form->createView(),
    );
  }

  /**
   * Deletes a SubscriptionService entity.
   * @Route("/operatori/subscription-service/{id}/delete", name="operatori_subscription-service_delete")
   * @Method("GET")
   * @param Request $request the request
   * @param SubscriptionService $subscriptionService The SubscriptionService entity
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function deleteSubscriptionServiceAction(Request $request, SubscriptionService $subscriptionService)
  {
    try {

      $em = $this->getDoctrine()->getManager();
      $em->remove($subscriptionService);
      $em->flush();

      $this->addFlash('feedback', 'Servizio a sottoscizione eliminato correttamente');

      return $this->redirectToRoute('operatori_subscription-service_index');
    } catch (ForeignKeyConstraintViolationException $exception) {
      $this->addFlash('warning', 'Impossibile eliminare il servizio a sottoscrizione, ci sono delle iscrizioni collegate.');
      return $this->redirectToRoute('operatori_subscription-service_index');
    }
  }

  /**
   * @Route("operatori/subscription-service/{subscriptionService}/edit", name="operatori_subscription-service_edit")
   * @ParamConverter("subscriptionService", class="AppBundle:SubscriptionService")
   * @Template()
   * @param Request $request the request
   * @param SubscriptionService $subscriptionService The SubscriptionService entity
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function editSubscriptionServiceAction(Request $request, SubscriptionService $subscriptionService)
  {
    $form = $this->createForm('AppBundle\Form\SubscriptionServiceType', $subscriptionService);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $em = $this->getDoctrine()->getManager();

      try {
        $em->persist($subscriptionService);
        $em->flush();

        $this->addFlash('feedback', 'Servizio a sottoscrizione modificato correttamente');
        return $this->redirectToRoute('operatori_subscription-service_index');
      } catch (\Exception $exception) {
        $this->addFlash('error', 'Codice servizio a sottoscrizione duplicato. Modifica fallita');
      }
    }

    return [
      'edit_form' => $form->createView(),
    ];
  }

  /**
   * Finds and displays a SubscriptionService entity.
   * @Template()
   * @Route("/operatori/subscription-service/{subscriptionService}", name="operatori_subscription-service_show")
   * @Method("GET")
   */
  public function showSubscriptionServiceAction(Request $request, SubscriptionService $subscriptionService)
  {
    $deleteForm = $this->createDeleteForm($subscriptionService);

    return array(
      'subscriptionService' => $subscriptionService,
      'delete_form' => $deleteForm->createView(),
    );
  }

  /**
   * Creates a form to delete a SubscriptionService entity.
   *
   * @param SubscriptionService $subscriptionService The SubscriptionService entity
   *
   * @return \Symfony\Component\Form\Form The form
   */
  private function createDeleteForm(SubscriptionService $subscriptionService)
  {
    return $this->createFormBuilder()
      ->setAction($this->generateUrl('operatori_subscription-service_delete', array('id' => $subscriptionService->getId())))
      ->setMethod('DELETE')
      ->getForm();
  }
}
