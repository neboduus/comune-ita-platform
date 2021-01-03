<?php

namespace App\Form\Base;

use App\Entity\PaymentGateway;
use App\Entity\Pratica;
use App\Entity\Servizio;
use App\Form\Extension\TestiAccompagnatoriProcedura;
use App\Payment\Gateway\MyPay;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
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
  private $gatewaysMap;

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


  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    /** @var TestiAccompagnatoriProcedura $helper */
    $helper = $options["helper"];
    $helper->setGuideText('steps.common.seleziona_gateway_pagament.guida_alla_compilazione', true);
    $helper->setStepTitle('steps.common.seleziona_gateway_pagament.title', true);

    /** @var Pratica $pratica */
    $pratica = $builder->getData();
    $tenantGateways = $pratica->getServizio()->getEnte()->getGateways();
    $availableGateways = array_keys($tenantGateways);

    $paymnetParameters = $pratica->getServizio()->getPaymentParameters();
    if ($paymnetParameters && !empty($paymnetParameters['gateways'])) {
      $availableGateways = array_keys($paymnetParameters['gateways']);
    }

    // Gateways abilitati
    $gateways = $this->em->getRepository('App:PaymentGateway')->findBy([
      'identifier' => $availableGateways
    ]);
    /** @var PaymentGateway $g */
    foreach ($gateways as $g) {
      $this->gatewaysMap[$g->getId()] = $g->getIdentifier();
    }

    $builder->add('payment_type', EntityType::class, [
      'class' => 'App\Entity\PaymentGateway',
      'choices' => $gateways,
      'choice_label' => 'name',
      'expanded' => true,
      'multiple' => false,
      'required' => true,
      'label' => 'Seleziona il metodo di pagamento',
      'choice_attr' => function($choice, $key, $value) {
        return ['data-identifier' => $choice->getIdentifier()];
      }
    ]);

    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
  }

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
    if ($this->gatewaysMap[$data['payment_type']] == 'mypay' && $pratica->getStatus() != Pratica::STATUS_PAYMENT_PENDING) {
      $this->statusService->setNewStatus($pratica, Pratica::STATUS_PAYMENT_PENDING);
    }


  }

  public function getBlockPrefix()
  {
    return 'pratica_select_payment_gateway';
  }
}