<?php

namespace App\Form\Base;

use App\Entity\Pratica;
use App\Form\Extension\TestiAccompagnatoriProcedura;
use App\Payment\AbstractPaymentData;
use App\Payment\Gateway\Bollo;
use App\Payment\Gateway\MyPay;
use App\Payment\GatewayCollection;
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
    $availableGateways = $this->gatewayCollection->getAvailablePaymentGateways();
    $gatewayClassHandler = $availableGateways[$pratica->getPaymentType()]['handler'];

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
