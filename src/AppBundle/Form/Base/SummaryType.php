<?php


namespace AppBundle\Form\Base;


use AppBundle\Entity\Pratica;
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

  public function __construct(EntityManagerInterface $entityManager, PraticaStatusService $statusService)
  {
    $this->em = $entityManager;
    $this->statusService = $statusService;
  }

  /**
   * @param FormBuilderInterface $builder
   * @param array $options
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {

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

    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
  }

  public function onPreSubmit(FormEvent $event)
  {

    // Se c'è un solo metodo di pagamneto lo imposto e salto lo step
    /** @var Pratica $application */
    $application = $event->getForm()->getData();

    $service = $application->getServizio();
    $paymentParameters = $service->getPaymentParameters();
    $selectedGateways = isset($paymentParameters['gateways']) ? $paymentParameters['gateways'] : [];

    if (count($selectedGateways) == 1) {
      $identifier = array_keys($selectedGateways)[0];
      $gateways = $this->em->getRepository('AppBundle:PaymentGateway')->findBy([
        'identifier' => array_keys($selectedGateways)[0]
      ]);
      if (count($gateways) > 0) {
        $application->setPaymentType($gateways[0]);
        $this->em->persist($application);
        $this->em->flush();
        if ($identifier == 'mypay' && $application->getStatus() != Pratica::STATUS_PAYMENT_PENDING) {
          $this->statusService->setNewStatus($application, Pratica::STATUS_PAYMENT_PENDING);
        }
      }
    }
  }

  public function getBlockPrefix()
  {
    return 'pratica_summary';
  }
}
