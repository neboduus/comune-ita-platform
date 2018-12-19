<?php

namespace AppBundle\Form\Base;

use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\VarDumper\VarDumper;

class SelectPaymentGatewayType extends AbstractType
{

    /**
     * @var EntityManager
     */
    private $em;

    public function __construct( EntityManager $entityManager )
    {
        $this->em = $entityManager;
    }


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $helper->setGuideText('steps.common.seleziona_gateway_pagament.guida_alla_compilazione', true);
        $helper->setStepTitle('steps.common.seleziona_gateway_pagament.title', true);

        $pratica  = $builder->getData();
        $entityRepository = $this->em->getRepository('AppBundle:PaymentGateway');
        $gateways = $entityRepository->findBy([
            'enabled' => 1
        ]);

        $builder->add('payment_type', EntityType::class, [
            'class' => 'AppBundle\Entity\PaymentGateway',
            'choices' => $gateways,
            'choice_label' => 'name',
            'expanded' => false,
            'multiple' => false,
            'required' => true,
            'label' => false,
            'placeholder' => 'Seleziona il metodo di pagamento'
        ]);
    }

    public function getBlockPrefix()
    {
        return 'pratica_select_payment_gateway';
    }
}
