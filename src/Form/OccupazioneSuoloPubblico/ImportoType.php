<?php
/**
 * Created by PhpStorm.
 * User: marco
 * Date: 07/12/18
 * Time: 10.53
 */

namespace App\Form\OccupazioneSuoloPubblico;


use App\Entity\Pratica;
use App\Payment\Gateway\MyPay;
use App\Services\MyPayService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ImportoType extends AbstractType
{

    /**
     * @var MyPayService
     */
    private $myPayService;

    public function __construct(MyPayService $myPayService) {
        $this->myPayService = $myPayService;
    }


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $helper = $options["helper"];

        $helper->setGuideText('steps.occupazione_suolo_pubblico.importo.guida_alla_compilazione',
            true);
        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));
        $builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'onPreSetData'));

    }

    public function getBlockPrefix()
    {
        return 'occupazione_suolo_pubblico_importo';
    }

    public function onPreSetData(FormEvent $event) {
        $form = $event->getForm();
        $options = $form->getConfig()->getOptions();

        /** @var Pratica $pratica */
        $pratica = $event->getData();

        $paymentData = $this->myPayService->getSanitizedPaymentData($pratica);

        $form->add('importo', MoneyType::class,array(
            'mapped' => false,
            'divisor' => 100,
            'required' => true,
            'attr' => array('min'=>1.0, 'max' => 10000),
            'data' => $paymentData[MyPay::IMPORTO]
        ));

    }

    public function onPostSubmit(FormEvent $event) {
        $form = $event->getForm();

        $form->getConfig()->getData();
        $pratica = $form->getData();

        $dd = $form->get('importo')->getNormData();

        if(!$dd || $dd <= 0) {
            $form->addError(new FormError('Inserire un importo valido'));
        } else {
            $data = $this->myPayService->getSanitizedPaymentData($pratica);
            $data[MyPay::IMPORTO] = $dd;
            /** @var Pratica $pratica */
            $pratica->setPaymentData($data);
        }
    }
}
