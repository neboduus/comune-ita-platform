<?php

namespace AppBundle\Payment\Gateway;


use AppBundle\Entity\Pratica;
use AppBundle\Payment\AbstractPaymentData;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\VarDumper\VarDumper;

class Bollo extends AbstractPaymentData implements EventSubscriberInterface
{
    public static function getFields()
    {
        return array(
            'bollo_identifier',
            'bollo_data_emissione',
            'bollo_ora_emissione',
        );
    }


    /** Event Subscriber **/
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'onPreSetData',
            FormEvents::PRE_SUBMIT   => 'onPreSubmit'
        );
    }


    public function onPreSetData(FormEvent $event)
    {
        /** @var Pratica $pratica */
        $pratica     = $event->getData();
        $paymentData = parent::fromData( $pratica->getPaymentData() );
        $identifier  = $paymentData->getFieldValue('bollo_identifier') ? $paymentData->getFieldValue('bollo_identifier') : null;
        $day         = $paymentData->getFieldValue('bollo_data_emissione') ? $paymentData->getFieldValue('bollo_data_emissione') : null;
        $hour        = $paymentData->getFieldValue('bollo_ora_emissione') ? implode(':', $paymentData->getFieldValue('bollo_ora_emissione')) : null;


        $form    = $event->getForm();
        $form
            ->add('bollo_identifier', TextType::class, [
            'label'    => 'Inserisci identificativo bollo',
            'data'     => (string)$identifier,
            'mapped'   => false,
            'required' => true]
            )
            ->add('bollo_data_emissione', DateType::class, [
                'required' => true,
                'mapped'   => false,
                'label'    => 'Inserisci data emissione bollo',
                'widget'   => 'single_text',
                'format'   => 'dd-MM-yyyy',
                'data'     => new \DateTime(  $day ),
                'attr' => [
                    'class' => 'form-control input-inline datepicker',
                    'data-provide' => 'datepicker',
                    'data-date-format' => 'dd-mm-yyyy'
                ]
            ])
            ->add('bollo_ora_emissione', TimeType::class, [
                'input'  => 'datetime',
                'widget' => 'choice',
                'with_seconds' => true,
                'mapped' => false,
                'data'   => new \DateTime( $hour ),
            ]);
    }


    public function onPreSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $paymentData = parent::fromArray($event->getData());
        $data['payment_data'] = $paymentData->toJson();
        $event->setData($data);
    }

}