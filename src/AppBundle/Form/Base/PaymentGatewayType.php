<?php

namespace AppBundle\Form\Base;

use AppBundle\Entity\Pratica;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use AppBundle\Payment\Gateway\MyPay;
use AppBundle\Services\MyPayService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;


class PaymentGatewayType extends AbstractType
{
  /**
   * @var EntityManager
   */
  private $em;

  /**
   * @var Container
   */
  private $container;

  public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container)
  {
    $this->em = $entityManager;
    $this->container = $container;
  }

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    /** @var Pratica $pratica */
    $pratica = $builder->getData();
    $entityRepository = $this->em->getRepository('AppBundle:PaymentGateway');
    $gateway = $pratica->getPaymentType();
    $gatewayClassHandler = $gateway->getFcqn();

    /** @var TestiAccompagnatoriProcedura $helper */
    $helper = $options["helper"];

    $helper->setStepTitle($gateway->getDescription());
    $helper->setGuideText($gateway->getDisclaimer());

    $paymentData = $pratica->getPaymentData() ?? [];

    if ($gatewayClassHandler === MyPay::class) {
      //This stinks like fresh cat shit
      //I wrote it, I know it
      $mypayService = $this->container->get(MyPayService::class);

      $pratica->setPaymentData($mypayService->getSanitizedPaymentData($pratica));

      $builder
        ->add('payment_data', HiddenType::class,
          [
            'mapped' => false,
            'required' => false,
          ]
        );
    } else {
      $builder
        ->add('payment_data', HiddenType::class,
          [
            'attr' => ['value' => json_encode($paymentData)],
            'mapped' => true,
            'required' => false,
          ]
        );
    }

    $builder->addEventSubscriber($this->container->get($gatewayClassHandler));
  }

  public function getBlockPrefix()
  {
    return 'pratica_payment_gateway';
  }
}
