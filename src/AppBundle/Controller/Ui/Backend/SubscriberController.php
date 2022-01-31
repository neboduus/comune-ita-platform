<?php

namespace AppBundle\Controller\Ui\Backend;

use AppBundle\Entity\Subscriber;
use AppBundle\Entity\SubscriptionService;
use AppBundle\Entity\User;
use AppBundle\Model\SubscriberMessage;
use AppBundle\Services\MailerService;
use Doctrine\ORM\EntityManager;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Omines\DataTablesBundle\Adapter\ArrayAdapter;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Controller\DataTablesTrait;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SubscriberController extends Controller
{
  use DataTablesTrait;

  /**
   * @var MailerService
   */
  private $mailer;

  private $defaultSender;
  /**
   * @var EntityManager
   */
  private $entityManager;

  /**
   * @var JWTTokenManagerInterface
   */
  private $JWTTokenManager;

  public function __construct(EntityManager $entityManager, MailerService $mailer, JWTTokenManagerInterface $JWTTokenManager, $defaultSender)
  {
    $this->entityManager = $entityManager;
    $this->mailer = $mailer;
    $this->JWTTokenManager = $JWTTokenManager;
    $this->defaultSender = $defaultSender;
  }

  /**
   * Finds and displays a SubscriptionService entity.
   * @Route("/operatori/subscriber/{subscriber}", name="operatori_subscriber_show")
   */
  public function showSubscriberAction(Request $request, Subscriber $subscriber)
  {
    /** @var User $user */
    $user = $this->getUser();

    $tab = $request->query->get('tab');
    $showSubscription = $request->query->get('show_subscription');

    $tableData = [];
    $subscribedSubscriptionServices = [];

    // retrieve datatables subscriber payments data
    foreach ($subscriber->getSubscriptions() as $subscription) {
      $subscribedSubscriptionServices[] = $subscription->getSubscriptionService()->getName();

      if ($subscription->getSubscriptionService()->getSubscriptionAmount())
        // Subscription Amount entry
        $tableData[] = array(
          'created_at' => $subscription->getCreatedAt(),
          'subscription_service_name' => $subscription->getSubscriptionService()->getName(),
          'subscription_service_code' => $subscription->getSubscriptionService()->getCode(),
          'subscription_service_id' => $subscription->getSubscriptionService()->getId(),
          'start_date' => $subscription->getSubscriptionService()->getBeginDate(),
          'end_date' => $subscription->getSubscriptionService()->getEndDate(),
          'payment_date' => $subscription->getSubscriptionService()->getBeginDate(),
          'payment_amount' => $subscription->getSubscriptionService()->getSubscriptionAmount()
        );
      // Subscription Payments entries
      foreach ($subscription->getSubscriptionService()->getSubscriptionPayments() as $payment) {
        $tableData[] = array(
          'created_at' => $subscription->getCreatedAt(),
          'subscription_service_name' => $subscription->getSubscriptionService()->getName(),
          'subscription_service_id' => $subscription->getSubscriptionService()->getId(),
          'subscription_service_code' => $subscription->getSubscriptionService()->getCode(),
          'start_date' => $subscription->getSubscriptionService()->getBeginDate(),
          'end_date' => $subscription->getSubscriptionService()->getEndDate(),
          'payment_date' => $payment->getDate(),
          'payment_amount' => $payment->getAmount(),
        );
      }
    }

    // Initializa datatable with previously created array data
    $table = $this->createDataTable()
      ->add('subscription_service_name', TextColumn::class, ['label' => 'Nome', 'searchable' => true, 'orderable' => true, 'render' => function ($value, $subscription) {
        return sprintf('<a href="%s">%s</a>', $this->generateUrl('operatori_subscription-service_show', [
          'subscriptionService' => $subscription['subscription_service_id']
        ]), $value);
      }])
      // ->add('subscription_service_code', TextColumn::class, ['label' => 'Codice', 'searchable' => true, 'orderable'=> true])
      ->add('created_at', DateTimeColumn::class, ['label' => 'Iscrizione', 'format' => 'd/m/Y', 'searchable' => false, 'orderable' => true])
      ->add('start_date', DateTimeColumn::class, ['label' => 'Inizio', 'format' => 'd/m/Y', 'searchable' => false, 'orderable' => true])
      ->add('end_date', DateTimeColumn::class, ['label' => 'Fine', 'format' => 'd/m/Y', 'searchable' => false, 'orderable' => true])
      //->add('payment_amount', TextColumn::class, ['label' => 'Importo', 'searchable' => false, 'orderable' => true])
      ->add('payment_amount', TextColumn::class, ['label' => 'Importo', 'searchable' => false, 'orderable' => true,  'render' => function ($value, $subscription) {
        return sprintf('<span>%s €</span>', number_format ( $value, 2 ));
      }])
      ->add('payment_date', DateTimeColumn::class, ['label' => 'Scadenza', 'format' => 'd/m/Y', 'searchable' => false, 'orderable' => true])
      /*->add('stato', TextColumn::class, ['label' => 'Stato', 'searchable' => false, 'orderable' => true, 'render' => function ($value, $subscription) {
        return sprintf('<svg class="icon icon-success"><use xlink:href="/bootstrap-italia/dist/svg/sprite.svg#it-check-circle"></use></svg>');
      }])*/
      ->createAdapter(ArrayAdapter::class, $tableData)
      ->handleRequest($request);

    if ($table->isCallback()) {
      return $table->getResponse();
    }

    // Message
    $subscriberMessage = new SubscriberMessage();
    $subscriberMessage->setSubscriber($subscriber);
    $messageForm = $this->createForm('AppBundle\Form\SubscriberMessageType', $subscriberMessage);
    $messageForm->handleRequest($request);

    if ($messageForm->isSubmitted() && $messageForm->isValid()) {
      $this->mailer->dispatchMailForSubscriber($subscriberMessage, $this->defaultSender, $this->getUser());
      $this->addFlash('feedback', 'Messaggio inviato');

      return $this->redirectToRoute('operatori_subscriber_show', ['subscriber' => $subscriber->getId()]);
    }

    $subscriptionServices = $this->entityManager->getRepository(SubscriptionService::class)->findAll();
    return $this->render( '@App/Subscriber/showSubscriber.html.twig', [
      'user' => $user,
      'subscriber' => $subscriber,
      'tab'=> $tab,
      'show_subscription' => $showSubscription,
      'datatable' => $table,
      'message_form' => $messageForm->createView(),
      'subscriptionServices'=> $subscriptionServices,
      'token' => $this->JWTTokenManager->create($user)
    ]);
  }
}
