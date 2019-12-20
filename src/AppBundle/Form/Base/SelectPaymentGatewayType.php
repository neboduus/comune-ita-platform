<?php

namespace AppBundle\Form\Base;

use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\VarDumper\VarDumper;
use AppBundle\Services\PraticaStatusService;

class SelectPaymentGatewayType extends AbstractType
{

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

    $pratica = $builder->getData();
    $entityRepository = $this->em->getRepository('AppBundle:PaymentGateway');
    $gateways = $entityRepository->findBy([
      'enabled' => 1
    ]);

    $builder->add('payment_type', EntityType::class, [
      'class' => 'AppBundle\Entity\PaymentGateway',
      'choices' => $gateways,
      'choice_label' => 'name',
      'expanded' => true,
      'multiple' => false,
      'required' => true,
      'label' => 'Seleziona il metodo di pagamento'
    ]);

    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
  }

  public function onPreSubmit(FormEvent $event)
  {
    /** @var Pratica $pratica */
    $pratica = $event->getForm()->getData();

    if ($pratica->getType() == Pratica::TYPE_FORMIO && $pratica->getStatus() != Pratica::STATUS_PAYMENT_PENDING) {
      $this->statusService->setNewStatus($pratica, Pratica::STATUS_PAYMENT_PENDING);
    }
    $this->em->persist($pratica);
  }

  public function getBlockPrefix()
  {
    return 'pratica_select_payment_gateway';
  }
}
