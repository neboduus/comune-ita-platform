<?php

namespace AppBundle\Form\Base;

use AppBundle\Entity\PaymentGateway;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use AppBundle\Payment\Gateway\MyPay;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\VarDumper\VarDumper;
use AppBundle\Services\PraticaStatusService;

class SelectPaymentGatewayType extends AbstractType
{
  private $gatewaysMap;

  /**
   * @var EntityManager
   */
  private $em;

  /**
   * @var
   */
  private $statusService;

  public function __construct(EntityManager $entityManager, PraticaStatusService $statusService)
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
    $gateways = $this->em->getRepository('AppBundle:PaymentGateway')->findBy([
      'identifier' => $availableGateways
    ]);
    /** @var PaymentGateway $g */
    foreach ($gateways as $g) {
      $this->gatewaysMap[$g->getId()] = $g->getIdentifier();
    }

    $builder->add('payment_type', EntityType::class, [
      'class' => 'AppBundle\Entity\PaymentGateway',
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

    if ($this->gatewaysMap[$data['payment_type']] == 'mypay' && $pratica->getStatus() != Pratica::STATUS_PAYMENT_PENDING) {
      $this->statusService->setNewStatus($pratica, Pratica::STATUS_PAYMENT_PENDING);
    }
    $this->em->persist($pratica);
  }

  public function getBlockPrefix()
  {
    return 'pratica_select_payment_gateway';
  }
}
