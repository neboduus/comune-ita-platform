<?php


namespace App\Controller\Ui\Frontend;

use App\BackOffice\SubcriptionsBackOffice;
use App\Entity\CPSUser;
use App\Entity\OperatoreUser;
use App\Entity\Servizio;
use App\Entity\Subscriber;
use App\Entity\Subscription;
use App\Entity\SubscriptionPayment;
use App\Entity\SubscriptionService;
use App\Entity\User;
use App\Services\BreadcrumbsService;
use App\Services\Manager\PraticaManager;
use App\Services\ModuloPdfBuilderService;
use App\Services\SubscriptionsService;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\TwigColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Contracts\Translation\TranslatorInterface;


/**
 * Class SubscriptionsController
 */
class SubscriptionsController extends AbstractController
{

  private $subscriptionsBackOffice;
  /**
   * @var EntityManagerInterface
   */
  private $entityManager;
  /**
   * @var TranslatorInterface
   */
  private $translator;
  /**
   * @var SubscriptionsService
   */
  private $subscriptionsService;
  /**
   * @var LoggerInterface
   */
  private $logger;
  /**
   * @var ModuloPdfBuilderService
   */
  private $pdfBuilderService;
  /**
   * @var BreadcrumbsService
   */
  private $breadcrumbsService;
  /**
   * @var JWTTokenManagerInterface
   */
  private $JWTTokenManager;
  /**
   * @var PraticaManager
   */
  private $praticaManager;

  /**
   * @param EntityManagerInterface $entityManager
   * @param LoggerInterface $logger
   * @param TranslatorInterface $translator
   * @param SubcriptionsBackOffice $subscriptionsBackOffice
   * @param SubscriptionsService $subscriptionsService
   * @param ModuloPdfBuilderService $pdfBuilderService
   * @param BreadcrumbsService $breadcrumbsService,
   * @param JWTTokenManagerInterface $JWTTokenManager
   */
  public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger, TranslatorInterface $translator, SubcriptionsBackOffice $subscriptionsBackOffice, SubscriptionsService $subscriptionsService, ModuloPdfBuilderService $pdfBuilderService, BreadcrumbsService $breadcrumbsService, JWTTokenManagerInterface $JWTTokenManager,
  PraticaManager $praticaManager)
  {
    $this->subscriptionsBackOffice = $subscriptionsBackOffice;
    $this->entityManager = $entityManager;
    $this->logger = $logger;
    $this->translator = $translator;
    $this->subscriptionsService = $subscriptionsService;
    $this->pdfBuilderService = $pdfBuilderService;
    $this->breadcrumbsService = $breadcrumbsService;
    $this->JWTTokenManager = $JWTTokenManager;
    $this->praticaManager = $praticaManager;
  }

  /**
   * Lists all subscriptions entities.
   * @Route("/operatori/subscriptions/{subscriptionService}", name="operatori_subscriptions")
   */
  public function showSubscriptionsAction(Request $request, SubscriptionService $subscriptionService, DataTableFactory $dataTableFactory)
  {
    /** @var OperatoreUser $user */
    $user = $this->getUser();

    $table = $dataTableFactory->create()
      ->add('show', TwigColumn::class, [
        'label' => '',
        'field' => 'subscriber.id',
        'searchable' => false,
        'orderable' => false,
        'template' => 'Subscriptions/table/_show.html.twig',
      ])
      ->add('name', TextColumn::class, [
        'label' => 'iscrizioni.subscribers.name',
        'field' => 'subscriber.name',
        'searchable' => true,
        'orderable' => true,
      ])
      ->add('surname', TextColumn::class, [
        'label' => 'iscrizioni.subscribers.surname',
        'field' => 'subscriber.surname',
        'searchable' => true,
        'orderable' => true
      ])
      ->add('fiscal_code', TextColumn::class, [
        'label' => 'iscrizioni.subscribers.fiscal_code',
        'field' => 'subscriber.fiscal_code',
        'searchable' => true,
      ])
      ->add('email', TwigColumn::class, [
        'label' => 'iscrizioni.subscribers.email_address',
        'field' => 'subscriber.email',
        'template' => 'Subscriptions/table/_email.html.twig',
        'searchable' => false,
        'orderable' => false
      ])
      ->add('created_at', DateTimeColumn::class, [
        'label' => 'iscrizioni.subscribers.created_at',
        'format' => 'd/m/Y',
        'searchable' => false,
        'orderable' => true
      ])
      ->add('subscriptionServiceId', TextColumn::class, [
        'label' => '',
        'field' => 'subscription_service.id',
        'searchable' => false,
        'visible'=>false
      ])
      ->add('status', TextColumn::class, [
        'label' => '',
        'searchable' => false,
        'visible'=>false
      ])
      ->add('actions', TwigColumn::class, [
        'label' => 'iscrizioni.subscribers.actions',
        'orderable' => false,
        'searchable' => false,
        'template' => 'Subscriptions/table/_actions.html.twig',
      ])
      ->createAdapter(ORMAdapter::class, [
        'entity' => Subscription::class,
        'query' => function (QueryBuilder $builder) use ($subscriptionService) {
          $builder
            ->select('subscription')
            ->addSelect('subscriber')
            ->from(Subscription::class, 'subscription')
            ->leftJoin('subscription.subscriber', 'subscriber')
            ->leftJoin('subscription.subscription_service', 'subscription_service')
            ->andWhere('subscription.subscription_service = :subscription_service')
            ->setParameter('subscription_service', $subscriptionService)
            ->orderBy('subscription.status', 'ASC')
            ->addOrderBy('subscriber.name', 'ASC');
        },
      ])
      ->handleRequest($request);

    if ($table->isCallback()) {
      return $table->getResponse();
    }

    $subscriptionServices = $this->entityManager->getRepository(SubscriptionService::class)->findAll();

    return $this->render('Subscriptions/showSubscriptions.html.twig', [
      'user' => $user,
      'datatable' => $table, 'subscriptionService' => $subscriptionService,
      'subscriptionServices'=> $subscriptionServices,
      'token' => $this->JWTTokenManager->create($user)
    ]);
  }

  /**
   * @param Request $request
   * @Route("/operatori/subscriptions/{subscriptionService}/upload-payment",name="operatori_import_payments_csv")
   * @Method("POST")
   * @return mixed
   * @throws \Exception
   */
  public function paymentsCsvUploadAction(Request $request, SubscriptionService $subscriptionService)
  {
    $uploadedFile = $request->files->get('upload');
    $paymentIdentifier = $request->get('payment');
    if (empty($uploadedFile)) {
      $this->logger->error("No file uploaded");
      return new JsonResponse(
        ["errors" => [$this->translator->trans('iscrizioni.missing_file')]],
        Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    if ($uploadedFile->getMimeType() != 'text/csv' && ($uploadedFile->getMimeType() == 'text/plain' && $uploadedFile->guessClientExtension() != 'csv')) {
      $this->logger->error("Incorrect uploaded file mimetype " . $uploadedFile->getMimeType() . " or invaild extension " . $uploadedFile->guessClientExtension());
      return new JsonResponse(
        ['errors' => [$this->translator->trans('iscrizioni.invalid_file')]],
        Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    $rows = $this->csv_to_array($uploadedFile->getPathname(), $this->detectDelimiter($uploadedFile));

    // Search subscription payment configuration
    $paymentConfig = null;
    foreach ($subscriptionService->getSubscriptionPayments() as $payment) {
      if ($payment->getPaymentIdentifier() === $paymentIdentifier) {
        $paymentConfig = $payment;
      }
    }

    if (!$paymentConfig) {
      $this->logger->error("No payment config with identifier " . $paymentIdentifier);
      return new JsonResponse(
        ['errors' => [$this->translator->trans('iscrizioni.missing_payment_config', ['%identifier%' => $paymentIdentifier])]],
        Response::HTTP_BAD_REQUEST);
    }

    /** @var Servizio $service */
    $service = $this->entityManager->getRepository(Servizio::class)->find($paymentConfig->getPaymentService());
    if (!$service) {
      $this->logger->error("No service found with id " . $paymentConfig->getPaymentService());
      return new JsonResponse(
        ['errors' => [$this->translator->trans('iscrizioni.no_payment_service')]],
        Response::HTTP_BAD_REQUEST);
    }

    if (!$rows) {
      $this->logger->error("Uploaded file is empty");
      return new JsonResponse(
        ['errors' => [$this->translator->trans('iscrizioni.empty_file')]],
        Response::HTTP_BAD_REQUEST);
    }

    if (!array_key_exists('fiscal_code', $rows[0]) || !array_key_exists('amount', $rows[0])) {
      $this->logger->error("Incorrect headers for uploaded file " . implode(',', array_keys($rows[0])));
      return new JsonResponse(
        ['errors' => [$this->translator->trans('backoffice.integration.fields_error')]],
        Response::HTTP_BAD_REQUEST);
    }

    $errors = [];
    $applications = [];


    foreach ($rows as $index => $row) {
      /** @var Subscriber $subscriber */
      $subscriber = $this->entityManager->getRepository(Subscriber::class)->findOneBy(['fiscal_code' => $row['fiscal_code']]);
      if (!$subscriber) {
        $errors[] = $this->translator->trans('iscrizioni.missing_subscriber', ['%index%'=>++$index, '%fiscal_code%' => $row['fiscal_code']]);
        $this->logger->error("Non subscriber found with fiscal code " . $row['fiscal_code']);
        continue;
      }

      $subscription = $this->entityManager->getRepository(Subscription::class)->findOneBy([
        'subscriber' => $subscriber,
        'subscription_service' => $subscriptionService
      ]);

      if (!$subscription) {
        $errors[] = $this->translator->trans('iscrizioni.missing_subscription', ['%index%'=>++$index, '%fiscal_code%' => $row['fiscal_code']]);
        $this->logger->error("Non subscription found for user " . $row['fiscal_code'] . " in subscription service " .  $subscriptionService->getCode());
        continue;
      }

      if (!$subscriber->isAdult() && empty($subscription->getRelatedCFs())) {
        $errors[] = $this->translator->trans('iscrizioni.underage_subscriber', ['%index%'=>++$index, '%fiscal_code%' => $row['fiscal_code']]);
        $this->logger->error("Underage subscriber " . $row['fiscal_code'] . ' with no delegates');
        continue;
      }

      $users = [];
      $user = $this->subscriptionsService->getOrCreateUserFromSubscriber($subscriber);
      if ($user) {
        // Create draft for subscription owner
        $users[] = $user;
      }

      // Create draft for subscription delegates
      foreach ($subscription->getRelatedCFs() as $relatedCF) {
        $user = $this->entityManager->getRepository(CPSUser::class)->findOneBy(['username' => $relatedCF]);
        if ($user) {
          $users[] = $user;
        }
      }

      if (empty($users)) {
        $errors[] = $this->translator->trans('iscrizioni.missing_related_cfs', ['%index%'=>++$index, '%fiscal_code%' => $row['fiscal_code']]);
        $this->logger->error("Underage subscriber " . $row['fiscal_code'] . " with no registered delegates " . implode(',', $subscription->getRelatedCFs()));
        continue;
      }

      $uniqueId = trim($paymentConfig->getPaymentIdentifier() . '_' . $subscription->getSubscriptionService()->getId() . '_' . $subscription->getSubscriber()->getFiscalCode());
      $dematerializedData = SubscriptionsService::getDematerializedFormForPayment($paymentConfig, $subscription, $row['amount'], $uniqueId);

      foreach ($users as $user) {
        // Check if application has already been created
        $results = $this->subscriptionsService->getDraftsApplicationForUser($user, $service, $uniqueId);

        if (!$results) {
          $application = $this->praticaManager->createDraftApplication($service, $user, $dematerializedData);
          $this->logger->info("Payment draft application created for user " . $user->getId() . "and identifier " . $paymentConfig->getPaymentIdentifier());
          $applications[] = $application;
          $this->subscriptionsService->sendEmailForDraftApplication($application, $subscription);

        } else {
          $this->logger->error("Payment draft application already exists for user " . $user->getId() . "and identifier " . $paymentConfig->getPaymentIdentifier());
          $errors[] = $this->translator->trans('iscrizioni.application_already_exists', [
            '%index%'=>++$index,
            '%user_fiscal_code%' => $user->getCodiceFiscale(),
            '%subscriber_fiscal_code%' => $subscriber->getFiscalCode()
          ]);
        }
      }
    }

    $import_message = $this->translator->transChoice('iscrizioni.drafts_created', count($applications), ["%num_applications%" => count($applications)]);

    return new JsonResponse(
      ['errors' => $errors, 'applications' => $applications, 'import_message'=>$import_message],
      $errors ? Response::HTTP_BAD_REQUEST : Response::HTTP_OK);
  }

  /**
   * @param Request $request
   * @Route("/operatori/subscriptions/{subscriptionService}/upload-subscribers",name="operatori_importa_csv_iscrizioni")
   * @Method("POST")
   * @return mixed
   * @throws \Exception
   */
  public function iscrizioniCsvUploadAction(Request $request, SubscriptionService $subscriptionService)
  {
    $uploadedFile = $request->files->get('upload');
    if (empty($uploadedFile)) {
      $this->logger->error("No file uploaded");
      return new JsonResponse(
        ["errors" => [$this->translator->trans('iscrizioni.missing_file')]],
        Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    if ($uploadedFile->getMimeType() != 'text/csv' && ($uploadedFile->getMimeType() == 'text/plain' && $uploadedFile->guessClientExtension() != 'csv')) {
      $this->logger->error("Incorrect uploaded file mimetype " . $uploadedFile->getMimeType() . " or invaild extension " . $uploadedFile->guessClientExtension());
      return new JsonResponse(
        ['errors' => [$this->translator->trans('iscrizioni.invalid_file')]],
        Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    $rows = $this->csv_to_array($uploadedFile->getPathname(), $this->detectDelimiter($uploadedFile));
    $errors = [];
    $subscriptions = [];

    // If subscriptions limits exceeds available space skip import
    if ($subscriptionService->getSubscribersLimit() && $subscriptionService->getSubscribersLimit() - $subscriptionService->getSubscriptions()->count() < count($rows)) {
      $this->logger->error("Subscribers limit reached for subscription service " . $subscriptionService->getCode());
      return new JsonResponse(
        ['errors' => [$this->translator->trans('iscrizioni.subscriptions_limit_reached')]],
        Response::HTTP_BAD_REQUEST);
    }

    foreach ($rows as $index => $row) {
      // No code provided: set default to current subscription service
      if (!array_key_exists('code', $row)) {
        $row['code'] = $subscriptionService->getCode();
      }


      if ($row['code'] == $subscriptionService->getCode()) {
        $subscription = $this->subscriptionsBackOffice->execute($row);
        if (!$subscription instanceof Subscription) {
          $this->logger->error($subscription["error"]);
          $errors[] = $this->translator->trans('iscrizioni.row_index', ['%index%' => ++$index]) . $subscription["error"];
        } else {
          $subscriptions[] = $subscription;
        }
      } else {
        $this->logger->error("Invalid code " . $row['code']);
        $errors[] = [$this->translator->trans(
          'iscrizioni.import_invalid_code', ['%index%' => ++$index, '%code%' => $row['code']]
        )];
      }
    }

    // Remove duplicates
    $errors = array_unique($errors);

    $import_message = $this->translator->transChoice('iscrizioni.subscriptions_created', count($subscriptions), ["%num_subscriptions%" => count($subscriptions)]);

    return new JsonResponse(
      ['errors' => $errors, 'subscriptions' => $subscriptions, 'import_message'=>$import_message],
      $errors ? Response::HTTP_BAD_REQUEST : Response::HTTP_OK);
  }

  /**
   * Deletes a Subscription entity.
   * @Route("/operatori/subscriptions/{id}/delete", name="operatori_subscription_delete")
   * @Method("GET")
   * @param Request $request the request
   * @param Subscription $subscription The Subscription entity
   * @return RedirectResponse
   */
  public function deleteSubscriptionAction(Request $request, Subscription $subscription)
  {
    try {
      $em = $this->getDoctrine()->getManager();
      $em->remove($subscription);
      $em->flush();

      $this->addFlash('feedback', $this->translator->trans('operatori.delete_subscription_service_success'));

      return $this->redirectToRoute('operatori_subscriptions', ['subscriptionService' => $subscription->getSubscriptionService()->getId()]);
    } catch (\Exception $exception) {
      $this->addFlash('warning', $this->translator->trans('operatori.delete_subscription_service_error'));
      return $this->redirectToRoute('operatori_subscriptions', ['subscriptionService' => $subscription->getSubscriptionService()->getId()]);
    }
  }

  protected function csv_to_array($filename = '', $delimiter = ',', $enclosure = '"')
  {
    if (!file_exists($filename) || !is_readable($filename))
      return FALSE;
    $header = NULL;
    $data = array();
    if (($handle = fopen($filename, 'r')) !== FALSE) {
      while (($row = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
        if (!$header) {
          $temp = array();
          foreach ($row as $r) {
            $temp [] = $r;
          }
          $header = $temp;
        } else {
          $data[] = array_combine($header, $row);
        }
      }
      fclose($handle);
    }
    return $data;
  }

  protected function detectDelimiter($csvFile)
  {
    $delimiters = array(
      ';' => 0,
      ',' => 0,
      "\t" => 0,
      "|" => 0
    );

    $handle = fopen($csvFile, "r");
    $firstLine = fgets($handle);
    fclose($handle);
    foreach ($delimiters as $delimiter => &$count) {
      $count = count(str_getcsv($firstLine, $delimiter));
    }

    return array_search(max($delimiters), $delimiters);
  }

  /**
   * @Route("/subscriptions/", name="subscriptions_list_cpsuser")
   * @return Response
   */
  public function cpsUserListSubscriptionAction(): Response
  {

    $this->breadcrumbsService->getBreadcrumbs()->addRouteItem('nav.iscrizioni', 'subscriptions_list_cpsuser');

    /** @var CPSUser $user */
    $user = $this->getUser();

    $userSubscriptions = $this->entityManager->createQueryBuilder()
      ->select('subscription')
      ->from(Subscription::class, 'subscription')
      ->join('subscription.subscriber', 'subscriber')
      ->where('subscriber.fiscal_code = :fiscal_code')
      ->setParameter('fiscal_code', $user->getCodiceFiscale())
      ->getQuery()->getResult();

    try {
      // Get shared subscriptions
      $sql = 'SELECT DISTINCT subscription.id from subscription where (related_cfs)::jsonb @> \'"' . $user->getCodiceFiscale() . '"\'';
      $stmt = $this->entityManager->getConnection()->prepare($sql);
      $result = $stmt->executeQuery();
      $sharedIds = $result->fetchAllAssociative();
    } catch (Exception | \Doctrine\DBAL\Exception $e) {
      $sharedIds = [];
    }

    foreach ($sharedIds as $id) {
      $userSubscriptions[] = $this->entityManager->getRepository('App\Entity\Subscription')->find($id);
    }

    return $this->render('Subscriptions/cpsUserListSubscription.html.twig', [
      'subscriptions' => $userSubscriptions,
      'user' => $user
    ]);
  }

  /**
   * @Route("/subscriptions/{subscriptionId}", name="subscription_show_cpsuser")
   * @param Request $request
   * @param $subscriptionId
   * @return RedirectResponse|Response|null
   */
  public function cpsUserShowSubscriptionAction(Request $request, $subscriptionId)
  {
    /** @var CPSUser $user */
    $user = $this->getUser();
    $subscription = $this->entityManager->getRepository('App\Entity\Subscription')->find($subscriptionId);

    if (!$subscription) {
      $this->addFlash('warning', $this->translator->trans('iscrizioni.no_iscrizione'));
      return $this->redirectToRoute('subscriptions_list_cpsuser');
    }

    if (!$this->canUserAccessSubscription($subscription)) {
      $this->addFlash('warning', $this->translator->trans('iscrizioni.accesso_negato'));
      return $this->redirectToRoute('subscriptions_list_cpsuser');
    }

    $this->breadcrumbsService->getBreadcrumbs()->addRouteItem('nav.iscrizioni', 'subscriptions_list_cpsuser');
    $this->breadcrumbsService->getBreadcrumbs()->addItem($subscription->getSubscriptionService()->getName());

    return $this->render('Subscriptions/cpsUserShowSubscription.html.twig', [
      'subscription' => $subscription,
      'user' => $user
    ]);
  }

  /**
   * @Route("/subscriptions/{subscriptionId}/payment/{subscriptionPaymentId}", name="subscription_payment_show_cpsuser")
   * @param Request $request
   * @param $subscriptionId
   * @param $subscriptionPaymentId
   * @return RedirectResponse|Response|null
   */
  public function cpsUserShowSubscriptionPaymentAction(Request $request, $subscriptionId, $subscriptionPaymentId)
  {
    /** @var CPSUser $user */
    $user = $this->getUser();
    /** @var SubscriptionPayment $subscriptionPayment */
    $subscriptionPayment = $this->entityManager->getRepository('App\Entity\SubscriptionPayment')->find($subscriptionPaymentId);

    if (!$subscriptionPayment or $subscriptionPayment->getSubscription()->getId() !== $subscriptionId) {
      $this->addFlash('warning', $this->translator->trans('iscrizioni.no_pagamento'));
      return $this->redirectToRoute('subscription_show_cpsuser', ["subscriptionId" => $subscriptionId]);
    }

    if (!$this->canUserAccessSubscription($subscriptionPayment->getSubscription())) {
      $this->addFlash('warning', $this->translator->trans('iscrizioni.accesso_negato'));
      return $this->redirectToRoute('subscriptions_list_cpsuser');
    }

    $this->breadcrumbsService->getBreadcrumbs()->addRouteItem('nav.iscrizioni', 'subscriptions_list_cpsuser');
    $this->breadcrumbsService->getBreadcrumbs()->addRouteItem($subscriptionPayment->getSubscription()->getSubscriptionService()->getName(), 'subscription_show_cpsuser', ['subscriptionId' => $subscriptionPayment->getSubscription()->getId()]);
    $this->breadcrumbsService->getBreadcrumbs()->addItem($subscriptionPayment->getName());

    return $this->render('Subscriptions/cpsUserShowSubscriptionPayment.html.twig', [
      'payment' => $subscriptionPayment,
      'user' => $user
    ]);
  }

  /**
   * @param Request $request
   * @param $subscriptionId
   * @param $subscriptionPaymentId
   * @return Response
   * @Route("/subscriptions/{subscriptionId}/certificate/{subscriptionPaymentId}", name="payment_certificate_download_cpsuser")
   */
  public function cpsUserPaymentCertificareDownloadAction(Request $request, $subscriptionId, $subscriptionPaymentId): Response
  {
    /** @var SubscriptionPayment $subscriptionPayment */
    $subscriptionPayment = $this->entityManager->getRepository('App\Entity\SubscriptionPayment')->find($subscriptionPaymentId);

    if (!$subscriptionPayment or $subscriptionPayment->getSubscription()->getId() !== $subscriptionId) {
      $this->addFlash('warning', $this->translator->trans('iscrizioni.no_pagamento'));
      return $this->redirectToRoute('subscription_show_cpsuser', ["subscriptionId" => $subscriptionId]);
    }

    if (!$this->canUserAccessSubscription($subscriptionPayment->getSubscription())) {
      $this->addFlash('warning', $this->translator->trans('iscrizioni.accesso_negato'));
      return $this->redirectToRoute('subscriptions_list_cpsuser');
    }

    return $this->createBinaryResponseForCertificate($subscriptionPayment);
  }

  /**
   * @param Request $request
   * @param $subscriptionId
   * @param $subscriptionPaymentId
   * @return Response
   * @Route("/operatori/subscriptions/{subscriptionId}/certificate/{subscriptionPaymentId}", name="payment_certificate_download_operatore")
   */
  public function operatorePaymentCertificareDownloadAction(Request $request, $subscriptionId, $subscriptionPaymentId): Response
  {
    /** @var SubscriptionPayment $subscriptionPayment */
    $subscriptionPayment = $this->entityManager->getRepository('App\Entity\SubscriptionPayment')->find($subscriptionPaymentId);

    if (!$subscriptionPayment or $subscriptionPayment->getSubscription()->getId() !== $subscriptionId) {
      $this->addFlash('warning', $this->translator->trans('iscrizioni.no_pagamento'));
      return $this->redirectToRoute('operatori_subscriptions');
    }

    return $this->createBinaryResponseForCertificate($subscriptionPayment);
  }


  /**
   * @param SubscriptionPayment $subscriptionPayment
   * @return Response
   */
  private function createBinaryResponseForCertificate(SubscriptionPayment $subscriptionPayment): Response
  {
    $fileContent = $this->pdfBuilderService->renderForSubscriptionPayment($subscriptionPayment);

    // Provide a name for your file with extension
    $filename = $subscriptionPayment->getId() . '.pdf';
    $response = new Response($fileContent);
    $disposition = $response->headers->makeDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      $filename
    );
    $response->headers->set('Content-Disposition', $disposition);
    return $response;
  }

  /**
   * @param Request $request
   * @param $subscriptionId
   * @param $fiscalCode
   * @return Response|void
   * @Route("/operatori/subscriptions/{subscriptionId}/unshare/{fiscalCode}", name="unshare_subscription_operatore")
   */
  public function operatoreUnshareSubscriptionAction(Request $request, $subscriptionId, $fiscalCode): Response
  {

    /** @var OperatoreUser $user */
    $user = $this->getUser();

    $subscription = $this->entityManager->getRepository('App\Entity\Subscription')->find($subscriptionId);

    if (!$subscription) {
      $this->addFlash('warning', $this->translator->trans('iscrizioni.no_iscrizione'));
      return $this->redirectToRoute('operatori_subscriptions');
    }

    $subscription = $subscription->removeRelatedCf($fiscalCode);
    try {
      $this->entityManager->persist($subscription);
      $this->entityManager->flush();
    } catch (ORMException $e) {
      $this->addFlash('danger', $this->translator->trans('iscrizioni.errore_salvataggio'));
    }

    return $this->redirectToRoute('operatori_subscriber_show', [
        'subscriber' => $subscription->getSubscriber()->getId(),
        'tab' => 'subscriptions',
        'show_subscription' => $subscriptionId]
    );
  }

  /**
   * @param Request $request
   * @param $subscriptionId
   * @param $fiscalCode
   * @return Response|void
   * @Route("/subscriptions/{subscriptionId}/unshare/{fiscalCode}", name="unshare_subscription_cpsuser")
   */
  public function cpsUserUnshareSubscriptionAction(Request $request, $subscriptionId, $fiscalCode): Response
  {

    /** @var Subscription $subscription */
    $subscription = $this->entityManager->getRepository('App\Entity\Subscription')->find($subscriptionId);

    if (!$subscription) {
      $this->addFlash('warning', $this->translator->trans('iscrizioni.no_iscrizione'));
      return $this->redirectToRoute('subscriptions_list_cpsuser');
    }

    if (!$this->canUserEditSubscription($subscription)) {
      $this->addFlash('warning', $this->translator->trans('iscrizioni.modifica_negata'));
      return $this->redirectToRoute('subscription_show_cpsuser', ['subscriptionId' => $subscriptionId]);
    }

    $subscription = $subscription->removeRelatedCf($fiscalCode);
    try {
      $this->entityManager->persist($subscription);
      $this->entityManager->flush();
    } catch (ORMException $e) {
      $this->addFlash('danger', $this->translator->trans('iscrizioni.errore_salvataggio'));
    }

    return $this->redirectToRoute('subscription_show_cpsuser', [
      'subscriptionId' => $subscriptionId
    ]);
  }


  /**
   * @param Request $request
   * @param $subscriptionId
   * @return Response|void
   * @Method("POST")
   * @Route("/operatori/subscriptions/{subscriptionId}/share", name="subscription_share_operatore")
   */
  public function operatoreShareSubscriptionAction(Request $request, $subscriptionId): Response
  {
    $subscription = $this->entityManager->getRepository('App\Entity\Subscription')->find($subscriptionId);

    if (!$subscription) {
      $this->addFlash('warning', $this->translator->trans('iscrizioni.no_iscrizione'));
      return $this->redirectToRoute('operatori_subscriptions');
    }

    $shares = explode(',', str_replace(' ', '', $request->get("shares")));
    foreach ($shares as $fiscalCode) {
      if (strlen($fiscalCode) !== 16) {
        $this->addFlash('warning', $this->translator->trans('iscrizioni.cf_non_valido', ["%fiscal_code%" => $fiscalCode]));
      } else {
        $subscription = $subscription->addRelatedCf($fiscalCode);
      }
    }

    try {
      $this->entityManager->persist($subscription);
      $this->entityManager->flush();
    } catch (ORMException $e) {
      $this->addFlash('danger', $this->translator->trans('iscrizioni.errore_salvataggio'));
    }

    return $this->redirectToRoute('operatori_subscriber_show', [
      'subscriber' => $subscription->getSubscriber()->getId(),
      'tab' => 'subscriptions',
      'show_subscription' => $subscriptionId
    ]);
  }

  /**
   * @param Request $request
   * @param $subscriptionId
   * @return Response|void
   * @Method("POST")
   * @Route("/subscriptions/{subscriptionId}/share", name="subscription_share_cpsuser")
   */
  public function cpsUserShareSubscriptionAction(Request $request, $subscriptionId): Response
  {
    /** @var Subscription $subscription */
    $subscription = $this->entityManager->getRepository('App\Entity\Subscription')->find($subscriptionId);

    if (!$subscription) {
      $this->addFlash('warning', $this->translator->trans('iscrizioni.no_iscrizione'));
      return $this->redirectToRoute('subscriptions_list_cpsuser');
    }

    if (!$this->canUserEditSubscription($subscription)) {
      $this->addFlash('warning', $this->translator->trans('iscrizioni.modifica_negata'));
      return $this->redirectToRoute('subscription_show_cpsuser', ['subscriptionId' => $subscriptionId]);
    }

    $shares = explode(',', str_replace(' ', '', $request->get("shares")));
    foreach ($shares as $fiscalCode) {
      if (strlen($fiscalCode) !== 16) {
        $this->addFlash('warning', $this->translator->trans('iscrizioni.cf_non_valido', ["%fiscal_code%" => $fiscalCode]));
      } else {
        $subscription = $subscription->addRelatedCf($fiscalCode);
      }
    }

    try {
      $this->entityManager->persist($subscription);
      $this->entityManager->flush();
    } catch (ORMException $e) {
      $this->addFlash('danger', $this->translator->trans('iscrizioni.errore_salvataggio'));
    }

    return $this->redirectToRoute('subscription_show_cpsuser', [
      'subscriptionId' => $subscriptionId
    ]);
  }

  /**
   * @Route("operatori/subscriber/{subscriber}/edit", name="operatori_subscriber_edit")
   * @ParamConverter("subscriber", class="App\Entity\Subscriber")
   * @param Request $request the request
   * @param Subscriber $subscriber The Subscriber entity
   *
   * @return Response
   */
  public function editSubscriberAction(Request $request, Subscriber $subscriber)
  {
    /** @var User $user */
    $user = $this->getUser();

    $form = $this->createForm('App\Form\SubscriberType', $subscriber, ['is_edit' => true]);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

      try {
        $this->entityManager->persist($subscriber);
        $this->entityManager->flush();
        return $this->redirectToRoute('operatori_subscriber_show', ['subscriber' => $subscriber->getId()]);
      } catch (\Exception $exception) {
        $this->addFlash('error', $this->translator->trans('backoffice.integration.subscriptions.save_subscriber_error', ['user' => $subscriber->getCompleteName()]));
      }
    }

    return $this->render('Subscriber/editSubscriber.html.twig', [
      'user' => $user,
      'form' => $form->createView(),
      'subscriber' => $subscriber
    ]);
  }


  /**
   * @param Subscription $subscription
   * @return bool
   */
  private function canUserAccessSubscription(Subscription $subscription): bool
  {
    /** @var CPSUser $user */
    $user = $this->getUser();

    if ($subscription->getSubscriber()->getFiscalCode() !== $user->getCodiceFiscale() and !in_array($user->getCodiceFiscale(), $subscription->getRelatedCFs())) {
      return false;
    }

    return true;
  }

  /**
   * @param Subscription $subscription
   * @return bool
   */
  private function canUserEditSubscription(Subscription $subscription): bool
  {
    /** @var CPSUser $user */
    $user = $this->getUser();

    if ($subscription->getSubscriber()->getFiscalCode() !== $user->getCodiceFiscale()) {
      return false;
    }

    return true;
  }
}
