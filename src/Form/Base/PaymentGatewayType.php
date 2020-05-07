<?php

namespace App\Form\Base;

use App\Entity\Pratica;
use App\Form\Extension\TestiAccompagnatoriProcedura;
use App\Payment\Gateway\MyPay;
use App\Payment\PaymentGatewayRegistry;
use App\Services\MyPayService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
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
     * @var PaymentGatewayRegistry
     */
    private $paymentGatewayRegistry;

    /**
     * @var MyPayService
     */
    private $myPayService;

    public function __construct(
        EntityManagerInterface $entityManager,
        PaymentGatewayRegistry $paymentGatewayRegistry,
        MyPayService $myPayService
    ) {
        $this->em = $entityManager;
        $this->paymentGatewayRegistry = $paymentGatewayRegistry;
        $this->myPayService = $myPayService;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     * @throws \Exception
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Pratica $pratica */
        $pratica = $builder->getData();
        $gateway = $pratica->getPaymentType();
        $gatewayClassHandler = $gateway->getFcqn();

        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];

        $helper->setStepTitle($gateway->getDescription());
        $helper->setGuideText($gateway->getDisclaimer());

        $paymentData = $pratica->getPaymentData() ?? [];

        if ($gatewayClassHandler === MyPay::class) {
            $pratica->setPaymentData($this->myPayService->getSanitizedPaymentData($pratica));

            $builder
                ->add(
                    'payment_data',
                    HiddenType::class,
                    [
                        'mapped' => false,
                        'required' => false,
                    ]
                );
        } else {
            $builder
                ->add(
                    'payment_data',
                    HiddenType::class,
                    [
                        'attr' => ['value' => json_encode($paymentData)],
                        'mapped' => true,
                        'required' => false,
                    ]
                );
        }

        $builder->addEventSubscriber($this->paymentGatewayRegistry->get($gatewayClassHandler));
    }

    public function getBlockPrefix()
    {
        return 'pratica_payment_gateway';
    }
}
