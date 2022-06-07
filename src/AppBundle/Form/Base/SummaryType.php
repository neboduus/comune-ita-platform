<?php


namespace AppBundle\Form\Base;


use AppBundle\Entity\PaymentGateway;
use AppBundle\Entity\Pratica;
use AppBundle\Form\Admin\Servizio\PaymentDataType;
use AppBundle\Services\PaymentService;
use AppBundle\Services\PraticaStatusService;
use Doctrine\ORM\EntityManagerInterface;
use EWZ\Bundle\RecaptchaBundle\Form\Type\EWZRecaptchaType;
use EWZ\Bundle\RecaptchaBundle\Validator\Constraints\IsTrue as RecaptchaTrue;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Class MessageType
 */
class SummaryType extends AbstractType
{


  /**
   * @var EntityManagerInterface
   */
  private $em;

  /**
   * @var
   */
  private $statusService;
  /**
   * @var PaymentService
   */
  private $paymentService;

  /**
   * @param EntityManagerInterface $entityManager
   * @param PraticaStatusService $statusService
   * @param PaymentService $paymentService
   */
  public function __construct(EntityManagerInterface $entityManager, PraticaStatusService $statusService, PaymentService $paymentService)
  {
    $this->em = $entityManager;
    $this->statusService = $statusService;
    $this->paymentService = $paymentService;
  }

  /**
   * @param FormBuilderInterface $builder
   * @param array $options
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    /** @var Pratica $pratica */
    $pratica = $builder->getData();

    // Add recaptcha if user is anonymous
    if ($pratica->getUser() == null) {
      $constraint = new RecaptchaTrue();
      $constraint->message = 'Questo valore non è un captcha valido.';
      $constraint->groups = ['recaptcha'];


      $builder
        ->add('recaptcha', EWZRecaptchaType::class,
          [
            'label' => false,
            'mapped' => false,
            'constraints' => [$constraint]
          ]);
    }

    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
  }

  public function onPreSubmit(FormEvent $event)
  {

    // Se c'è un solo metodo di pagamneto lo imposto e salto lo step
    /** @var Pratica $application */
    $application = $event->getForm()->getData();
    $data = $application->getDematerializedForms();

    // Todo: attivare anche se payment_amount non è presente ma pè stato configurato il valore nel servizio, verificare per pagamento posticipato
    if (isset($data['flattened'][PaymentDataType::PAYMENT_AMOUNT]) && $data['flattened'][PaymentDataType::PAYMENT_AMOUNT] > 0) {
      $service = $application->getServizio();
      $paymentParameters = $service->getPaymentParameters();
      $selectedGateways = isset($paymentParameters['gateways']) ? $paymentParameters['gateways'] : [];
      if (count($selectedGateways) == 1) {
        $identifier = array_keys($selectedGateways)[0];
        if ($identifier) {
          /** @var PaymentGateway $gateway */
          $application->setPaymentType($identifier);
          $this->em->persist($application);
          $this->em->flush();
          if ($identifier != 'bollo' && $identifier != 'mypay' && $application->getStatus() != Pratica::STATUS_PAYMENT_PENDING) {
            $application->setPaymentData($this->paymentService->createPaymentData($application));
            $this->em->persist($application);
            $this->em->flush();
            $this->statusService->setNewStatus($application, Pratica::STATUS_PAYMENT_PENDING);
          }
        }
      }
    }
  }

  public function getBlockPrefix()
  {
    return 'pratica_summary';
  }
}
