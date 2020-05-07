<?php

namespace App\Controller;

use App\Entity\Subscriber;
use App\Model\SubscriberMessage;
use App\Services\MailerService;
use Omines\DataTablesBundle\Adapter\ArrayAdapter;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SubscriberController extends Controller
{
    /**
     * @var MailerService
     */
    private $mailer;

    private $defaultSender;

    public function __construct(MailerService $mailer, $defaultSender)
    {
        $this->mailer = $mailer;
        $this->defaultSender = $defaultSender;
    }

    /**
     * Finds and displays a SubscriptionService entity.
     * @Route("/operatori/subscriber/{subscriber}", name="operatori_subscriber_show")
     * @param Request $request
     * @param Subscriber $subscriber
     * @param DataTableFactory $dataTableFactory
     * @return Response|RedirectResponse
     * @throws \Exception
     */
    public function showSubscriber(Request $request, Subscriber $subscriber, DataTableFactory $dataTableFactory)
    {
        $tableData = [];
        $subscriptionServices = [];

        // retrieve datatables subscriber payments data
        foreach ($subscriber->getSubscriptions() as $subscription) {
            $subscriptionServices[] = $subscription->getSubscriptionService()->getName();

            if ($subscription->getSubscriptionService()->getSubscriptionAmount()) {
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
            }
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
        $table = $dataTableFactory->create()
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
            ->add('payment_amount', TextColumn::class, ['label' => 'Importo', 'searchable' => false, 'orderable' => true, 'render' => function ($value, $subscription) {
                return sprintf('<span>%s €</span>', number_format($value, 2));
            }])
            ->add('payment_date', DateTimeColumn::class, ['label' => 'Scadenza', 'format' => 'd/m/Y', 'searchable' => false, 'orderable' => true])
            ->add('stato', TextColumn::class, ['label' => 'Stato', 'searchable' => false, 'orderable' => true, 'render' => function ($value, $subscription) {
                return sprintf('<svg class="icon icon-success"><use xlink:href="/bootstrap-italia/dist/svg/sprite.svg#it-check-circle"></use></svg>');
            }])
            ->createAdapter(ArrayAdapter::class, $tableData)
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        // Message
        $subscriberMessage = new SubscriberMessage();
        $subscriberMessage->setSubscriber($subscriber);
        $form = $this->createForm('App\Form\SubscriberMessageType', $subscriberMessage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->mailer->dispatchMailForSubscriber($subscriberMessage, $this->defaultSender, $this->getUser());
            $this->addFlash('feedback', 'Messaggio inviato');

            return $this->redirectToRoute('operatori_subscriber_show', ['subscriber' => $subscriber->getId()]);
        }

        return $this->render('Subscriber/index.html.twig', [
            'subscriber' => $subscriber,
            'datatable' => $table,
            'form' => $form->createView(),
        ]);
    }
}
