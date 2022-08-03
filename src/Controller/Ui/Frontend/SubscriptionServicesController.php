<?php

namespace App\Controller\Ui\Frontend;

use App\BackOffice\SubcriptionsBackOffice;
use App\Entity\Servizio;
use App\Entity\Subscriber;
use App\Entity\Subscription;
use App\Entity\SubscriptionPayment;
use App\Entity\SubscriptionService;
use App\Entity\User;
use App\Services\SubscriptionsService;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\EntityManagerInterface;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\MapColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\TwigColumn;
use Omines\DataTablesBundle\Controller\DataTablesTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;


/**
 * Class SubscriptionServicesController
 */
class SubscriptionServicesController extends Controller
{
  use DataTablesTrait;

  /**
   * @var EntityManagerInterface
   */
  private $em;
  /**
   * @var TranslatorInterface
   */
  private $translator;
  /**
   * @var SubcriptionsBackOffice
   */
  private $subcriptionsBackOffice;
  /**
   * @var SubscriptionsService
   */
  private $subscriptionsService;

  /**
   * SubscriptionServicesController constructor.
   * @param EntityManagerInterface $entityManager
   */
  public function __construct(EntityManagerInterface $entityManager, TranslatorInterface $translator, SubcriptionsBackOffice $subcriptionsBackOffice, SubscriptionsService $subscriptionsService)
  {
    $this->em = $entityManager;
    $this->translator = $translator;
    $this->subcriptionsBackOffice = $subcriptionsBackOffice;
    $this->subscriptionsService = $subscriptionsService;
  }

  /**
   * Lists all SubscriptionService entities.
   * @Route("/operatori/subscription-service", name="operatori_subscription-service_index")
   */
  public function indexSubscriptionServiceAction(Request $request)
  {
    /** @var User $user */
    $user = $this->getUser();
    $statuses = [
      SubscriptionService::STATUS_WAITING => $this->translator->trans('meetings.status.draft'),
      SubscriptionService::STATUS_ACTIVE => $this->translator->trans('webhook.active'),
      SubscriptionService::STATUS_UNACTIVE => $this->translator->trans('webhook.not_Active')
    ];
    $items = $this->em->getRepository('App\Entity\SubscriptionService')->findAll();


    $table = $this->createDataTable()
      ->add('name', TwigColumn::class, [
        'className' => 'text-truncate',
        'label' => 'iscrizioni.nome',
        'orderable' => true,
        'searchable' => true,
        'template' => 'SubscriptionServices/table/_name.html.twig',
      ])
      ->add('code', TextColumn::class, ['label' => 'iscrizioni.codice', 'searchable' => true])
      ->add('status', MapColumn::class, ['label' => 'iscrizioni.stato', 'searchable' => false, 'map' => $statuses])
      ->add('subscriptions', TwigColumn::class, [
        'className' => 'text-truncate',
        'label' => 'iscrizioni.subscriptions',
        'orderable' => false,
        'searchable' => false,
        'template' => 'SubscriptionServices/table/_subscriptions.html.twig',
      ])
      ->add('subscriptionPayments', TwigColumn::class, [
        'className' => 'text-truncate',
        'label' => 'backoffice.integration.subscription_service.payments',
        'orderable' => false,
        'searchable' => false,
        'template' => 'SubscriptionServices/table/_payments.html.twig',
      ])
      ->add('beginDate', DateTimeColumn::class, ['label' => 'iscrizioni.data_inizio', 'format' => 'd/m/Y', 'searchable' => false])
      ->add('endDate', DateTimeColumn::class, ['label' => 'iscrizioni.data_fine', 'format' => 'd/m/Y', 'searchable' => false])
      ->add('id', TwigColumn::class, [
        'className' => 'text-truncate',
        'label' => 'iscrizioni.subscribers.actions',
        'orderable' => false,
        'searchable' => false,
        'template' => 'SubscriptionServices/table/_actions.html.twig',
      ])
      ->createAdapter(ORMAdapter::class, [
        'entity' => SubscriptionService::class
      ])
      ->handleRequest($request);

    if ($table->isCallback()) {
      return $table->getResponse();
    }

    return $this->render('SubscriptionServices/indexSubscriptionService.html.twig', [
      'user' => $user,
      'items' => $items,
      'statuses' => $statuses,
      'datatable' => $table
    ]);
  }

  /**
   * @Route("/operatori/subscribers-template-csv",name="operatori_download_subscribers_template_csv")
   * @param Request $request
   */
  public function downloadSubscribersTemplateAction(Request $request)
  {
    $responseCallback = function () use ($request) {
      $csvHeaders = $this->subcriptionsBackOffice->getRequiredHeaders();

      $handle = fopen('php://output', 'w');
      fputcsv($handle, $csvHeaders);
      fclose($handle);
    };

    $fileName = 'subscribers_template.csv';
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
   * @Route("/operatori/subscriptions-payments-template-csv",name="operatori_download_subscription_payments_template_csv")
   * @param Request $request
   */
  public function downloadSubscriptionPaymentsTemplateAction(Request $request)
  {
    $responseCallback = function () use ($request) {
      $csvHeaders = array(
        'fiscal_code',
        'amount'
      );

      $handle = fopen('php://output', 'w');
      fputcsv($handle, $csvHeaders);
      fclose($handle);
    };

    $fileName = 'subscription_payments_template.csv';
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

    $subscriptionServices = $this->em->getRepository('App\Entity\SubscriptionService')->findAll();

    $subscriptionService = new SubscriptionService();
    $form = $this->createForm('App\Form\SubscriptionServiceType', $subscriptionService);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

      try {
        $this->em->persist($subscriptionService);
        $this->em->flush();

        $this->addFlash('feedback', $this->translator->trans('backoffice.integration.subscription_service.created'));
        return $this->redirectToRoute('operatori_subscription-service_index');
      } catch (\Exception $exception) {
        $this->addFlash('error', $this->translator->trans('backoffice.integration.subscription_service.duplicate_error'));
      }
    }

    $services = $this->em->getRepository(Servizio::class)->findAvailableForSubscriptionPaymentSettings();

    return $this->render('SubscriptionServices/newSubscriptionService.html.twig', [
      'user' => $user,
      'subscriptionService' => $subscriptionService,
      'form' => $form->createView(),
      'subscriptionServices' => $subscriptionServices,
      'services' => $services
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

      $this->em->remove($subscriptionService);
      $this->em->flush();

      $this->addFlash('feedback', $this->translator->trans('backoffice.integration.subscription_service.deleted'));

      return $this->redirectToRoute('operatori_subscription-service_index');
    } catch (ForeignKeyConstraintViolationException $exception) {
      $this->addFlash('warning', $this->translator->trans('backoffice.integration.subscription_service.delete_error'));
      return $this->redirectToRoute('operatori_subscription-service_index');
    }
  }

  /**
   * @Route("operatori/subscription-service/{subscriptionService}/edit", name="operatori_subscription-service_edit")
   * @ParamConverter("subscriptionService", class="App:SubscriptionService")
   * @param Request $request the request
   * @param SubscriptionService $subscriptionService The SubscriptionService entity
   *
   * @return Response
   */
  public function editSubscriptionServiceAction(Request $request, SubscriptionService $subscriptionService)
  {
    /** @var User $user */
    $user = $this->getUser();

    $subscriptionServices = $this->em->getRepository('App\Entity\SubscriptionService')->findAll();

    $form = $this->createForm('App\Form\SubscriptionServiceType', $subscriptionService);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

      try {
        $this->em->persist($subscriptionService);
        $this->em->flush();

        $this->addFlash('feedback', $this->translator->trans('backoffice.integration.subscription_service.edited'));
        return $this->redirectToRoute('operatori_subscription-service_index');
      } catch (\Exception $exception) {
        $this->addFlash('error', $this->translator->trans('backoffice.integration.subscription_service.duplicate_code_error'));
      }
    }

    $services = $this->em->getRepository(Servizio::class)->findAvailableForSubscriptionPaymentSettings();

    return $this->render('SubscriptionServices/editSubscriptionService.html.twig', [
      'user' => $user,
      'form' => $form->createView(),
      'subscriptionServices' => $subscriptionServices,
      'services' => $services
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

    $subscriptionServicePayments = [];
    foreach ($subscriptionService->getSubscriptionPayments() as $paymentSetting) {
      try {
        $payments = $this->em->createQueryBuilder()
          ->select('count(distinct payment.subscription)')
          ->from(SubscriptionPayment::class, 'payment')
          ->join('payment.subscription', 'subscription')
          ->where('subscription.subscription_service = :subscriptionServiceId')
          ->andWhere('payment.name = :identifier')
          ->setParameter('subscriptionServiceId', $subscriptionService->getId())
          ->setParameter('identifier', $paymentSetting->getPaymentIdentifier())
          ->getQuery()->getSingleScalarResult();
      } catch (NoResultException|NonUniqueResultException $e) {
        $payments = 0;
      }

      $subscriptionServicePayments[$paymentSetting->getPaymentIdentifier()] = $payments;
    }

    $deleteForm = $this->createDeleteForm($subscriptionService);
    return $this->render('SubscriptionServices/showSubscriptionService.html.twig', [
      'user' => $user,
      'subscriptionService' => $subscriptionService,
      'payments' => $subscriptionServicePayments,
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
    $items = $this->em->getRepository('App\Entity\SubscriptionPayment')->findBy([], ['paymentDate' => 'DESC']);

    return $this->render('SubscriptionServices/indexSubscriptionServicePayments.html.twig', [
      'user' => $this->getUser(),
      'items' => $items,
      'identifiers' => $this->subscriptionsService->getPaymentSettingIdententifiers()
    ]);
  }

  /**
   * Lists all SubscriptionService entities.
   * @Route("/operatori/subscription-service-search", name="operatori_subscription-service_search")
   */
  public function searchSubscriptionsAction(Request $request): JsonResponse
  {
    $query = $request->query->get('q');
    $subscribers = [];
    if ($query) {
      $query = strtolower($query);
      $subscribers = $this->em->createQueryBuilder()
        ->select('subscriber')
        ->from(Subscriber::class, 'subscriber')
        ->andWhere("LOWER(CONCAT(subscriber.name,' ',subscriber.surname)) LIKE '%$query%' OR LOWER(subscriber.fiscal_code) LIKE '%$query%'")
        ->orderBy('subscriber.name', 'ASC')
        ->getQuery()->getResult();
    }

    return new JsonResponse($this->render('SubscriptionServices/parts/searchResults.html.twig', ['subscribers' =>$subscribers])->getContent(), Response::HTTP_OK);
  }

  /**
   * Show all subscription service payments
   * @Route("/operatori/subscription-service/{subscriptionService}/payments/{identifier}", name="operatori_subscription-service_payments_show")
   * @Method("GET")
   */
  public function showSubscriptionServicePaymentsAction(Request $request, SubscriptionService $subscriptionService, string $identifier)
  {
    /** @var User $user */
    $user = $this->getUser();

    $identifier = urldecode($identifier);
    $paymentsMade = $this->em->createQueryBuilder()
      ->select('payment', 'subscriber', 'subscription')
      ->from(SubscriptionPayment::class, 'payment')
      ->join('payment.subscription', 'subscription')
      ->join('subscription.subscriber', 'subscriber')
      ->where('subscription.subscription_service = :subscriptionService')
      ->setParameter('subscriptionService', $subscriptionService->getId())
      ->andWhere('payment.name = :paymentIdentifier')
      ->setParameter('paymentIdentifier', $identifier)
      ->getQuery()->getResult();

    $excludedPayments = [];
    foreach ($paymentsMade as $paymentMade) {
      $excludedPayments[] = $paymentMade->getSubscription();
    }
    $qb = $this->em->createQueryBuilder()
      ->select('subscription', 'subscriber')
      ->from(Subscription::class, 'subscription')
      ->join('subscription.subscriber', 'subscriber')
      ->where('subscription.subscription_service = :subscriptionService')
      ->setParameter('subscriptionService', $subscriptionService->getId());

    if (count($paymentsMade) > 0) {
      $qb
        ->andWhere('subscription NOT IN (:payers)')
        ->setParameter('payers', $excludedPayments);
    }

    $missingPayment = $qb->getQuery()->getResult();

    return $this->render('SubscriptionServices/showSubscriptionServicePayments.html.twig', [
      'user' => $user,
      'subscriptionService' => $subscriptionService,
      'paymentsMade' => $paymentsMade,
      'missingPayments' => $missingPayment,
      'identifier' => $identifier
    ]);
  }
}
