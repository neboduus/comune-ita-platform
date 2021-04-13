<?php


namespace AppBundle\Controller;

use AppBundle\BackOffice\SubcriptionsBackOffice;
use AppBundle\Entity\CPSUser;
use AppBundle\Entity\OperatoreUser;
use AppBundle\Entity\Subscription;
use AppBundle\Entity\SubscriptionPayment;
use AppBundle\Entity\SubscriptionService;

use AppBundle\Entity\User;
use AppBundle\Services\ModuloPdfBuilderService;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Controller\DataTablesTrait;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Translation\TranslatorInterface;


/**
 * Class SubscriptionsController
 */
class SubscriptionsController extends Controller
{
  use DataTablesTrait;

  private $subscriptionsBackOffice;
  /**
   * @var EntityManager
   */
  private $entityManager;
  /**
   * @var TranslatorInterface
   */
  private $translator;
  /**
   * @var ModuloPdfBuilderService
   */
  private $pdfBuilderService;

  public function __construct(EntityManager $entityManager, ModuloPdfBuilderService $pdfBuilderService, TranslatorInterface $translator, SubcriptionsBackOffice $subscriptionsBackOffice)
  {
    $this->subscriptionsBackOffice = $subscriptionsBackOffice;
    $this->entityManager = $entityManager;
    $this->translator = $translator;
    $this->pdfBuilderService = $pdfBuilderService;
  }

  /**
   * Lists all subscriptions entities.
   * @Template()
   * @Route("/operatori/subscriptions/{subscriptionService}", name="operatori_subscriptions")
   */
  public function showSubscriptionsAction(Request $request, SubscriptionService $subscriptionService)
  {
    /** @var OperatoreUser $user */
    $user = $this->getUser();

    $table = $this->createDataTable()
      ->add('show', TextColumn::class, ['label' => 'show', 'field' => 'subscriber.id', 'searchable' => false, 'orderable' => false, 'render' => function ($value, $subscriptionService) {
        return sprintf('<a href="%s"><svg class="icon icon-sm icon-primary"><use xlink:href="/bootstrap-italia/dist/svg/sprite.svg#it-zoom-in"></use></svg></a>', $this->generateUrl('operatori_subscriber_show', [
          'subscriber' => $value
        ]), $value);
      }])
      ->add('name', TextColumn::class, ['label' => 'name', 'field' => 'subscriber.name', 'searchable' => true])
      ->add('surname', TextColumn::class, ['label' => 'surname', 'field' => 'subscriber.surname', 'searchable' => true])
      ->add('fiscal_code', TextColumn::class, ['label' => 'fiscal_code', 'field' => 'subscriber.fiscal_code', 'searchable' => true])
      ->add('email', TextColumn::class, ['label' => 'email_address', 'field' => 'subscriber.email', 'render' => function ($value, $subscriptionService) {
        return sprintf('<a href="mailto:%s"><div class="text-truncate">%s</div></a>', $value, $value);
      }])
      ->add('created_at', DateTimeColumn::class, ['label' => 'created_at', 'format' => 'd/m/Y', 'searchable' => false])
      ->add('id', TextColumn::class, ['label' => 'Azioni', 'searchable' => false, 'render' => function ($value, $subscriptionService) {
        return sprintf('
        <a class="d-inline-block d-sm-none d-lg-inline-block d-xl-none" href="%s" onclick="return confirm(\'Sei sicuro di procedere? la sottoscrizione verrà eliminato definitivamente.\');"><svg class="icon icon-sm icon-danger"><use xlink:href="/bootstrap-italia/dist/svg/sprite.svg#it-delete"></use></svg></a>
        <a class="btn btn-danger btn-sm d-none d-sm-inline-block d-lg-none d-xl-inline-block" href="%s" onclick="return confirm(\'Sei sicuro di procedere? la sottoscrizione verrà eliminato definitivamente.\');">Elimina</a>',
          $this->generateUrl('operatori_subscription_delete', ['id' => $value]),
          $this->generateUrl('operatori_subscription_delete', ['id' => $value])
        );
      }])
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
            ->setParameter('subscription_service', $subscriptionService);
        },
      ])
      ->handleRequest($request);

    if ($table->isCallback()) {
      return $table->getResponse();
    }

    return array(
      'user' => $user,
      'datatable' => $table, 'subscriptionService' => $subscriptionService
    );
  }

  /**
   * @param Request $request
   * @Route("/operatori/subscriptions/{subscriptionService}/upload",name="operatori_importa_csv_iscrizioni")
   * @Method("POST")
   * @return mixed
   * @throws \Exception
   */
  public function iscrizioniCsvUploadAction(Request $request, SubscriptionService $subscriptionService)
  {
    $uploadedFile = $request->files->get('upload');
    if (empty($uploadedFile)) {
      return new Response('Error: no file imported', Response::HTTP_UNPROCESSABLE_ENTITY,
        ['content-type' => 'text/plain']);
    }

    if ($uploadedFile->getMimeType() != 'text/csv' && ($uploadedFile->getMimeType() == 'text/plain' && $uploadedFile->guessClientExtension() != 'csv')) {
      return new Response('Invalid file', Response::HTTP_UNPROCESSABLE_ENTITY,
        ['content-type' => 'text/plain']);
    }
    $rows = $this->csv_to_array($uploadedFile->getPathname());

    // create response object
    $response = new stdClass();
    $response->errors = [];

    // If subscriptions limits exceedes available space skip import
    if ($subscriptionService->getSubscribersLimit() && $subscriptionService->getSubscribersLimit() - $subscriptionService->getSubscriptions()->count() < count($rows)) {
      $response->errors[] = ['error' => 'Il numero di iscrizioni è superiore al numero massimo consentito'];
    } else {
      foreach ($rows as $row) {
        // No code provided: set default to current subscription service
        if (!array_key_exists('code', $row)) {
          $row['code'] = $subscriptionService->getCode();
        }
        if ($row['code'] == $subscriptionService->getCode()) {
          $subscription = $this->subscriptionsBackOffice->execute($row);
          if (!$subscription instanceof Subscription) {
            // error
            $response->errors[] = $subscription;
          }
        }
      }
    }

    // Remove duplicates
    $response->errors = array_map('unserialize', array_unique(array_map('serialize', $response->errors)));
    if (count($response->errors) > 0) {
      return new Response(json_encode($response), Response::HTTP_BAD_REQUEST, ['content-type' => 'application/json']);
    } else {
      return new Response("Subscriptions correctly imported", Response::HTTP_OK, ['content-type' => 'text/plain']);
    }
  }

  /**
   * Creates a form to delete a Subscription entity.
   *
   * @param Subscription $subscription The Subscription entity
   *
   * @return \Symfony\Component\Form\Form The form
   */
  private function createDeleteForm(Subscription $subscription)
  {
    return $this->createFormBuilder()
      ->setAction($this->generateUrl('operatori_subscription_delete', array('id' => $subscription->getId())))
      ->setMethod('DELETE')
      ->getForm();
  }

  /**
   * Deletes a Subscription entity.
   * @Route("/operatori/subscription/{id}/delete", name="operatori_subscription_delete")
   * @Method("GET")
   * @param Request $request the request
   * @param Subscription $subscription The Subscription entity
   * @return RedirectResponse
   */
  public function deleteSubscriptionAction(Request $request, Subscription $subscription)
  {
    $subscriber = $subscription->getSubscriber();
    try {
      $em = $this->getDoctrine()->getManager();
      $em->remove($subscription);
      $em->flush();

      $this->addFlash('feedback', 'Sottoscrizione eliminata correttamente');

      return $this->redirectToRoute('operatori_subscriber_show', ['subscriber' => $subscriber->getId()]);
    } catch (\Exception $exception) {
      $this->addFlash('warning', 'Impossibile eliminare la sottoscrizione.');
      return $this->redirectToRoute('operatori_subscriber_show', ['subscriber' => $subscriber->getId()]);
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

  /**
   * @Route("/subscriptions/", name="subscriptions_list_cpsuser")
   * @Template()
   * @return array
   */
  public function cpsUserListSubscriptionAction(): array
  {
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
      $stmt->execute();
      $sharedIds = $stmt->fetchAll();
    } catch (Exception | \Doctrine\DBAL\Exception $e) {
      $sharedIds = [];
    }

    foreach ($sharedIds as $id) {
      $userSubscriptions[] = $this->entityManager->getRepository('AppBundle:Subscription')->find($id);
    }

    return [
      'subscriptions' => $userSubscriptions,
      'user' => $user
    ];
  }

  /**
   * @Route("/subscriptions/{subscriptionId}", name="subscription_show_cpsuser")
   * @Template()
   * @param Request $request
   * @param $subscriptionId
   * @return array|Response
   */
  public function cpsUserShowSubscriptionAction(Request $request, $subscriptionId)
  {
    /** @var CPSUser $user */
    $user = $this->getUser();
    $subscription = $this->entityManager->getRepository('AppBundle:Subscription')->find($subscriptionId);

    if (!$subscription) {
      $this->addFlash('warning', $this->translator->trans('iscrizioni.no_iscrizione'));
      return $this->redirectToRoute('subscriptions_list_cpsuser');
    }

    if (!$this->canUserAccessSubscription($subscription)) {
      $this->addFlash('warning', $this->translator->trans('iscrizioni.accesso_negato'));
      return $this->redirectToRoute('subscriptions_list_cpsuser');
    }

    return [
      'subscription' => $subscription,
      'user' => $user
    ];
  }

  /**
   * @Route("/subscriptions/{subscriptionId}/payment/{subscriptionPaymentId}", name="subscription_payment_show_cpsuser")
   * @Template()
   * @param Request $request
   * @param $subscriptionId
   * @param $subscriptionPaymentId
   * @return array|Response
   */
  public function cpsUserShowSubscriptionPaymentAction(Request $request, $subscriptionId, $subscriptionPaymentId)
  {
    /** @var CPSUser $user */
    $user = $this->getUser();
    $subscriptionPayment = $this->entityManager->getRepository('AppBundle:SubscriptionPayment')->find($subscriptionPaymentId);

    if (!$subscriptionPayment or $subscriptionPayment->getSubscription()->getId() !== $subscriptionId) {
      $this->addFlash('warning', $this->translator->trans('iscrizioni.no_pagamento'));
      return $this->redirectToRoute('subscription_show_cpsuser', ["subscriptionId" => $subscriptionId]);
    }


    if (!$this->canUserAccessSubscription($subscriptionPayment->getSubscription())) {
      $this->addFlash('warning', $this->translator->trans('iscrizioni.accesso_negato'));
      return $this->redirectToRoute('subscriptions_list_cpsuser');
    }

    return [
      'payment' => $subscriptionPayment,
      'user' => $user
    ];
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
    $subscriptionPayment = $this->entityManager->getRepository('AppBundle:SubscriptionPayment')->find($subscriptionPaymentId);

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
   * @Route("/operatori/{subscriptionId}/certificate/{subscriptionPaymentId}", name="payment_certificate_download_operatore")
   */
  public function operatorePaymentCertificareDownloadAction(Request $request, $subscriptionId, $subscriptionPaymentId): Response
  {
    /** @var SubscriptionPayment $subscriptionPayment */
    $subscriptionPayment = $this->entityManager->getRepository('AppBundle:SubscriptionPayment')->find($subscriptionPaymentId);

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
   * @Route("/operatori/{subscriptionId}/unshare/{fiscalCode}", name="unshare_subscription_operatore")
   */
  public function operatoreUnshareSubscriptionAction(Request $request, $subscriptionId, $fiscalCode): Response
  {

    /** @var OperatoreUser $user */
    $user = $this->getUser();

    $subscription = $this->entityManager->getRepository('AppBundle:Subscription')->find($subscriptionId);

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
    $subscription = $this->entityManager->getRepository('AppBundle:Subscription')->find($subscriptionId);

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
   * @Route("/operatori/{subscriptionId}/share", name="subscription_share_operatore")
   */
  public function operatoreShareSubscriptionAction(Request $request, $subscriptionId): Response
  {
    $subscription = $this->entityManager->getRepository('AppBundle:Subscription')->find($subscriptionId);

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
    $subscription = $this->entityManager->getRepository('AppBundle:Subscription')->find($subscriptionId);

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
