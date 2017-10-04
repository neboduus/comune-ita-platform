<?php

namespace AppBundle\Form\Base;

use AppBundle\Entity\Pratica;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use AppBundle\Payment\AbstractPaymentData;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\VarDumper\VarDumper;


class PaymentGatewayType extends AbstractType
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
        /** @var Pratica $pratica */
        $pratica  = $builder->getData();
        $entityRepository = $this->em->getRepository('AppBundle:PaymentGateway');
        $gateway = $pratica->getPaymentType();


        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $helper->setStepTitle($gateway->getDescription());
        $helper->setGuideText($gateway->getDisclaimer());

        $paymentData = $pratica->getPaymentData() ? $pratica->getPaymentData() : [];
        $builder
            ->add('payment_data', HiddenType::class,
                [
                    'attr' => ['value' =>  json_encode($paymentData)],
                    'mapped' => true,
                    'required' => false,
                ]
            );

        $gatewayClassHandler = $gateway->getFcqn();
        $builder->addEventSubscriber(new $gatewayClassHandler());
    }

    public function getBlockPrefix()
    {
        return 'pratica_payment_gateway';
    }
}
