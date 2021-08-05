<?php

namespace AppBundle\Controller\Ui\Frontend;

use AppBundle\BackOffice\SubcriptionsBackOffice;
use AppBundle\Entity\SubscriptionService;
use AppBundle\Entity\User;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\MapColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Controller\DataTablesTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;


/**
 * Class SubscriptionServicesController
 */
class SubscriptionServicesController extends Controller
{
  use DataTablesTrait;

  /**
   * Lists all SubscriptionService entities.
   * @Route("/operatori/subscription-service", name="operatori_subscription-service_index")
   */
  public function indexSubscriptionServiceAction(Request $request)
  {
    /** @var User $user */
    $user = $this->getUser();
    $statuses = [
      SubscriptionService::STATUS_WAITING => 'Pending',
      SubscriptionService::STATUS_ACTIVE => 'Attivo',
      SubscriptionService::STATUS_UNACTIVE => 'Inattivo'
    ];
    $em = $this->getDoctrine()->getManager();
    $items = $em->getRepository('AppBundle:SubscriptionService')->findAll();


    $table = $this->createDataTable()
      ->add('name', TextColumn::class, ['label' => 'Nome', 'propertyPath' => 'Nome', 'render' => function ($value, $subscriptionService) {
        return sprintf('<a href="%s">%s</a>', $this->generateUrl('operatori_subscription-service_show', [
          'subscriptionService' => $subscriptionService->getId()
        ]), $subscriptionService->getName());
      }])
      ->add('code', TextColumn::class, ['label' => 'Codice', 'searchable' => true])
      ->add('status', MapColumn::class, ['label' => 'Stato', 'searchable' => false, 'map' => $statuses])
      ->add('subscriptions', TextColumn::class, ['label' => 'Iscrizioni', 'render' => function ($value, $subscriptionService) {
        if ($subscriptionService->getSubscribersLimit()) {
          return sprintf('<a href="%s">%s</a>', $this->generateUrl('operatori_subscriptions',
            ['subscriptionService' => $subscriptionService->getId()]),
            count($subscriptionService->getSubscriptions()) . ' di ' . $subscriptionService->getSubscribersLimit());
        } else {
          return sprintf('<a href="%s">%s</a>', $this->generateUrl('operatori_subscriptions',
            ['subscriptionService' => $subscriptionService->getId()]),
            count($subscriptionService->getSubscriptions()));
        }
      }])
      ->add('beginDate', DateTimeColumn::class, ['label' => 'Data di inizio', 'format' => 'd/m/Y', 'searchable' => false])
      ->add('endDate', DateTimeColumn::class, ['label' => 'Data di fine', 'format' => 'd/m/Y', 'searchable' => false])
      ->add('id', TextColumn::class, ['label' => 'Azioni', 'searchable' => false, 'render' => function ($value, $subscriptionService) {
        return sprintf('
        <a class="d-inline-block d-sm-none d-lg-inline-block d-xl-none" href="%s"><svg class="icon icon-sm icon-warning"><use xlink:href="/bootstrap-italia/dist/svg/sprite.svg#it-pencil"></use></svg></a>
        <a class="btn btn-warning btn-sm d-none d-sm-inline-block d-lg-none d-xl-inline-block" href="%s">Modifica</a>
        <a class="d-inline-block d-sm-none d-lg-inline-block d-xl-none" href="%s" onclick="return confirm(\'Sei sicuro di procedere? il servizio a sottoscrizione verrà eliminato definitivamente.\');"><svg class="icon icon-sm icon-danger"><use xlink:href="/bootstrap-italia/dist/svg/sprite.svg#it-delete"></use></svg></a>
        <a class="btn btn-danger btn-sm d-none d-sm-inline-block d-lg-none d-xl-inline-block" href="%s" onclick="return confirm(\'Sei sicuro di procedere? il servizio a sottoscrizione verrà eliminato definitivamente.\');">Elimina</a>',
          $this->generateUrl('operatori_subscription-service_edit', ['subscriptionService' => $value]),
          $this->generateUrl('operatori_subscription-service_edit', ['subscriptionService' => $value]),
          $this->generateUrl('operatori_subscription-service_delete', ['id' => $value]),
          $this->generateUrl('operatori_subscription-service_delete', ['id' => $value])
        );
      }])
      ->createAdapter(ORMAdapter::class, [
        'entity' => SubscriptionService::class
      ])
      ->handleRequest($request);

    if ($table->isCallback()) {
      return $table->getResponse();
    }

    return $this->render('@App/SubscriptionServices/indexSubscriptionService.html.twig', [
      'user' => $user,
      'items' => $items,
      'statuses' => $statuses,
      'datatable' => $table
    ]);
  }

  /**
   * @Route("/operatori/subscription-service-csv",name="operatori_subscriptions_template_csv")
   * @param Request $request
   */
  public function indexCSVAction(Request $request)
  {
    $responseCallback = function () use ($request) {
      $csvHeaders = $this->container->get(SubcriptionsBackOffice::class)->getRequiredHeaders();

      $handle = fopen('php://output', 'w');
      fputcsv($handle, $csvHeaders);
      fclose($handle);
    };

    $fileName = 'template.csv';
    $response = new StreamedResponse();
    $response->headers->set('Content-Encoding', 'none');
    $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
    $response->headers->set('X-Accel-Buffering', 'no');
    $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      $fileName
    ));
    $response->headers->set('Content-Description', 'File Transfer');
    $response->setStatusCode(Response::HTTP_OK);
    $response->setCallback($responseCallback);
    $response->send();
  }

  /**
   * Creates a new SubscriptionService entity.
   * @Route("/operatori/subscription-service/new", name="operatori_subscription-service_new")
   * @Method({"GET", "POST"})
   * @param Request $request the request
   * @return Response
   */
  public function newSubscriptionServiceAction(Request $request)
  {
    /** @var User $user */
    $user = $this->getUser();

    $subscriptionServices =$this->getDoctrine()->getRepository('AppBundle:SubscriptionService')->findAll();

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

    return $this->render('@App/SubscriptionServices/newSubscriptionService.html.twig', [
      'user' => $user,
      'subscriptionService' => $subscriptionService,
      'form' => $form->createView(),
      'subscriptionServices' => $subscriptionServices
    ]);
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
   * @param Request $request the request
   * @param SubscriptionService $subscriptionService The SubscriptionService entity
   *
   * @return Response
   */
  public function editSubscriptionServiceAction(Request $request, SubscriptionService $subscriptionService)
  {
    /** @var User $user */
    $user = $this->getUser();

    $subscriptionServices =$this->getDoctrine()->getRepository('AppBundle:SubscriptionService')->findAll();

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

    return $this->render('@App/SubscriptionServices/editSubscriptionService.html.twig', [
      'user' => $user,
      'form' => $form->createView(),
      'subscriptionServices' => $subscriptionServices
    ]);
  }

  /**
   * Finds and displays a SubscriptionService entity.
   * @Route("/operatori/subscription-service/{subscriptionService}", name="operatori_subscription-service_show")
   * @Method("GET")
   */
  public function showSubscriptionServiceAction(Request $request, SubscriptionService $subscriptionService)
  {
    /** @var User $user */
    $user = $this->getUser();

    $deleteForm = $this->createDeleteForm($subscriptionService);
    return $this->render('@App/SubscriptionServices/showSubscriptionService.html.twig', [
      'user' => $user,
      'subscriptionService' => $subscriptionService,
      'delete_form' => $deleteForm->createView(),
    ]);
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

  /**
   * Redirect to SubscriptionService.
   * @Route("/operatori/subscription-service-payments", name="operatori_subscription-service_payments_index")
   */
  public function indexSubscriptionServicePaymentsAction(Request $request)
  {
    return $this->redirectToRoute('operatori_subscription-service_index');
  }
}
