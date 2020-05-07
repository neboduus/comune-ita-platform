<?php

namespace App\Form\Admin\Ente;

use App\BackOffice\BackOfficeInterface;
use App\Entity\Ente;
use App\Entity\PaymentGateway;
use App\Form\Base\BlockQuoteType;
use App\Model\Gateway;
use App\Payment\PaymentGatewayRegistry;
use App\Services\BackOfficeCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class EnteType extends AbstractType
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var BackOfficeCollection
     */
    private $backOfficeCollection;

    private $paymentGatewayRegistry;

    public function __construct(EntityManagerInterface $entityManager, BackOfficeCollection $backOffices, PaymentGatewayRegistry $paymentGatewayRegistry)
    {
        $this->em = $entityManager;
        $this->backOfficeCollection = $backOffices;
        $this->paymentGatewayRegistry = $paymentGatewayRegistry;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     * @throws \Exception
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Ente $ente */
        $ente = $builder->getData();
        /** @var PaymentGateway[] $availableGateways */
        $availableGateways = $this->em->getRepository('App:PaymentGateway')->findBy([
            'enabled' => 1
        ]);

        $gateways = [];
        foreach ($availableGateways as $g) {
            $gateways[$g->getName()] = $g->getIdentifier();
        }

        $selectedGateways = $ente->getGateways();
        $selectedGatewaysIentifiers = [];
        $selectedGatewaysParameters = [];

        foreach ($selectedGateways as $s) {
            if ($s instanceof Gateway) {
                $selectedGatewaysIentifiers [] = $s->getIdentifier();
                $selectedGatewaysParameters[$s->getIdentifier()] = $s->getParameters();
            } else {
                $selectedGatewaysIentifiers [] = $s['identifier'];
                $selectedGatewaysParameters [$s['identifier']] = $s['parameters'];
            }
        }

        /*$backOffices = [
          'Aggiungi iscrizione' => "App\BackOffice\SubcriptionsBackOffice",
          'Prenota appuntamento' => "App\BackOffice\CalendarsBackOffice"
        ];*/

        $backOfficesData = [];
        /** @var BackOfficeInterface $b */
        foreach ($this->backOfficeCollection->getBackOffices() as $b) {
            $backOfficesData[$b->getName()] = $b->getPath();
        }

        $builder
            ->add('codice_meccanografico', TextType::class)
            ->add('site_url', TextType::class)
            ->add('codice_amministrativo', TextType::class)
            ->add('meta', TextareaType::class, ['required' => false]);

        /*$builder->add('backoffice_integration_enabled', CheckboxType::class, [
          'label' => 'Abilita integrazione con i backoffice',
          'required' => false
        ]);*/

        if (!empty($backOfficesData)) {
            $builder->add('backoffice_enabled_integrations', ChoiceType::class, [
                'choices' => $backOfficesData,
                'expanded' => true,
                'multiple' => true,
                'required' => false,
                'label' => 'Abilita integrazione con i backoffice',
            ]);
        }

        $builder->add('gateways', ChoiceType::class, [
            'data' => $selectedGatewaysIentifiers,
            'choices' => $gateways,
            'mapped' => false,
            'expanded' => true,
            'multiple' => true,
            'required' => false,
            'label' => 'Seleziona i metodi di pagamento disponibili per l\'ente',
        ]);

        foreach ($availableGateways as $g) {
            $paymentGateway = $this->paymentGatewayRegistry->get($g->getFcqn());
            $parameters = $paymentGateway::getPaymentParameters();
            if (count($parameters) > 0) {
                $gatewaySubform = $builder->create($g->getIdentifier(), FormType::class, [
                    'label' => false,
                    'mapped' => false,
                    'required' => false,
                    'attr' => ['class' => 'gateway-form-type d-none']
                ]);

                $gatewaySubform->add($g->getIdentifier() . '_label', BlockQuoteType::class, [
                    'label' => 'Parametri necessari per ' . $g->getName() . ' ( lasciare in binaco se si desidera impostare i valori a livello di servizio)'
                ]);

                foreach ($parameters as $k => $v) {
                    $gatewaySubform->add(
                        $k,
                        TextType::class,
                        [
                            'label' => $v,
                            'required' => false,
                            'data' => isset($selectedGatewaysParameters[$g->getIdentifier()][$k]) ? $selectedGatewaysParameters[$g->getIdentifier()][$k] : '',
                            'mapped' => false
                        ]
                    );
                }

                $builder->add($gatewaySubform);
            }
        }

        $builder->add('save', SubmitType::class, ['label' => 'Salva']);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
    }

    /**
     * @param FormEvent $event
     * @throws \Doctrine\ORM\ORMException
     */
    public function onPreSubmit(FormEvent $event)
    {
        /** @var Ente $ente */
        $ente = $event->getForm()->getData();
        $data = $event->getData();

        $gateways = [];
        if (isset($data['gateways']) && !empty($data['gateways'])) {
            foreach ($data['gateways'] as $g) {
                $gateway = new Gateway();
                $gateway->setIdentifier($g);

                if (isset($data[$g])) {
                    $gateway->setParameters($data[$g]);
                } else {
                    $gateway->setParameters(null);
                }
                $gateways[$g] = $gateway;
            }
        }
        $ente->setGateways($gateways);
        $this->em->persist($ente);
    }

    public function getBlockPrefix()
    {
        return 'ente';
    }
}
