<?php

namespace AppBundle\Form\Base;

use AppBundle\Entity\Pratica;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use AppBundle\Payment\AbstractPaymentData;
use AppBundle\Payment\Gateway\Bollo;
use AppBundle\Payment\Gateway\MyPay;
use AppBundle\Payment\GatewayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;


class PaymentGatewayType extends AbstractType
{

  /** @var EntityManagerInterface */
  private $entityManager;

  /** @var ContainerInterface */
  private $container;

  /**@var GatewayCollection */
  private $gatewayCollection;

  public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container, GatewayCollection $gatewayCollection)
  {
    $this->entityManager = $entityManager;
    $this->container = $container;
    $this->gatewayCollection = $gatewayCollection;
  }

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    /** @var Pratica $pratica */
    $pratica = $builder->getData();
    $availableGateways = $this->gatewayCollection->getHandlers();
    $gatewayClassHandler = $availableGateways[$pratica->getPaymentType()];

    /** @var TestiAccompagnatoriProcedura $helper */
    $helper = $options["helper"];

    /*$helper->setStepTitle($gateway->getDescription());
    $helper->setGuideText($gateway->getDisclaimer());*/

    $paymentData = $pratica->getPaymentData() ?? [];

    if ($gatewayClassHandler instanceof Bollo) {
      $builder
        ->add('payment_data', HiddenType::class,
          [
            'attr' => ['value' => json_encode($paymentData)],
            'mapped' => true,
            'required' => false,
          ]
        );
    } elseif ($gatewayClassHandler instanceof MyPay) {
      $pratica->setPaymentData(AbstractPaymentData::getSanitizedPaymentData($pratica));
      $builder
        ->add('payment_data', HiddenType::class,
          [
            'mapped' => false,
            'required' => false,
          ]
        );
    }

    $builder->addEventSubscriber($gatewayClassHandler);
  }

  public function getBlockPrefix()
  {
    return 'pratica_payment_gateway';
  }
}
