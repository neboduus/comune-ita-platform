<?php

namespace App\Form\Base;

use App\Entity\Pratica;
use App\Entity\Servizio;
use App\Form\Extension\TestiAccompagnatoriProcedura;
use App\Payment\Gateway\MyPay;
use App\Payment\GatewayCollection;
use App\Services\PaymentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\VarDumper\VarDumper;
use App\Services\PraticaStatusService;

class SelectPaymentGatewayType extends AbstractType
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
   * @var GatewayCollection
   */
  private $gatewayCollection;

  public function __construct(EntityManagerInterface $entityManager, PraticaStatusService $statusService, GatewayCollection $gatewayCollection)
  {
    $this->em = $entityManager;
    $this->statusService = $statusService;
    $this->gatewayCollection = $gatewayCollection;
  }


  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    /** @var TestiAccompagnatoriProcedura $helper */
    $helper = $options["helper"];
    $helper->setGuideText('steps.common.seleziona_gateway_pagament.guida_alla_compilazione', true);
    $helper->setStepTitle('steps.common.seleziona_gateway_pagament.title', true);

    /** @var Pratica $pratica */
    $pratica = $builder->getData();

    $serviceGateways = $pratica->getServizio()->getPaymentParameters();
    $normalizedTenantGateways = [];
    if (isset($serviceGateways['gateways'])) {
      foreach ($serviceGateways['gateways'] as $s) {
        $normalizedTenantGateways [$s['identifier']] = $s;
      }
      $serviceGateways = $normalizedTenantGateways;
    }

    $availableGateways = $this->gatewayCollection->getAvailablePaymentGateways();
    $gateways = [];
    foreach ($serviceGateways as $g) {
      $identifier = $g['identifier'];
      if (isset($availableGateways[$identifier])) {
        $gateways[$availableGateways[$identifier]['name']] = $identifier;
      }
    }

    $builder->add('payment_type', ChoiceType::class, [
      'choices' => $gateways,
      'expanded' => true,
      'multiple' => false,
      'required' => true,
      'label' => 'Seleziona il metodo di pagamento',
    ]);

    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
  }

  /**
   * @throws \Exception
   */
  public function onPreSubmit(FormEvent $event)
  {
    /** @var Pratica $pratica */
    $pratica = $event->getForm()->getData();
    $data = $event->getData();

    if (!isset($data['payment_type']) || empty($data)) {
      $event->getForm()->addError(
        new FormError('Devi scegliere almeno un metodo di pagamento')
      );
      return;
    }

    $this->em->persist($pratica);
    if ($data['payment_type'] != 'bollo' && $pratica->getStatus() != Pratica::STATUS_PAYMENT_PENDING) {
      $this->statusService->setNewStatus($pratica, Pratica::STATUS_PAYMENT_PENDING);
    }

  }

  public function getBlockPrefix()
  {
    return 'pratica_select_payment_gateway';
  }
}
