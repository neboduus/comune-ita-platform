<?php

namespace AppBundle\Controller\Ui\Backend;

use AppBundle\Entity\Subscriber;
use AppBundle\Entity\SubscriptionService;
use AppBundle\Entity\User;
use AppBundle\Model\SubscriberMessage;
use AppBundle\Services\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use AppBundle\Services\SubscriptionsService;
use Doctrine\ORM\EntityManager;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

class SubscriberController extends Controller
{
  /**
   * @var MailerService
   */
  private $mailer;

  private $defaultSender;
  /**
   * @var EntityManagerInterface
   */
  private $entityManager;

  /**
   * @var JWTTokenManagerInterface
   */
  private $JWTTokenManager;
  /**
   * @var SubscriptionsService
   */
  private $subscriptionsService;

  /** @var TranslatorInterface */
  private $translator;


  public function __construct(
    TranslatorInterface $translator,
    EntityManager $entityManager,
    MailerService $mailer,
    JWTTokenManagerInterface $JWTTokenManager,
    SubscriptionsService $subscriptionsService,
    $defaultSender
  )
  {
    $this->translator = $translator;
    $this->entityManager = $entityManager;
    $this->mailer = $mailer;
    $this->JWTTokenManager = $JWTTokenManager;
    $this->defaultSender = $defaultSender;
    $this->subscriptionsService = $subscriptionsService;

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

    // Message
    $subscriberMessage = new SubscriberMessage();
    $subscriberMessage->setSubscriber($subscriber);
    $messageForm = $this->createForm('AppBundle\Form\SubscriberMessageType', $subscriberMessage);
    $messageForm->handleRequest($request);

    if ($messageForm->isSubmitted() && $messageForm->isValid()) {
      $this->mailer->dispatchMailForSubscriber($subscriberMessage, $this->defaultSender, $this->getUser());
      $this->addFlash('feedback', $this->translator->trans('iscrizioni.send_message'));

      return $this->redirectToRoute('operatori_subscriber_show', ['subscriber' => $subscriber->getId()]);
    }

    $subscriptionServices = $this->entityManager->getRepository(SubscriptionService::class)->findAll();
    return $this->render( '@App/Subscriber/showSubscriber.html.twig', [
      'user' => $user,
      'subscriber' => $subscriber,
      'tab'=> $tab,
      'show_subscription' => $showSubscription,
      'message_form' => $messageForm->createView(),
      'subscriptionServices'=> $subscriptionServices,
      'token' => $this->JWTTokenManager->create($user),
      'identifiers' => $this->subscriptionsService->getPaymentSettingIdententifiers()
    ]);
  }
}
