<?php

namespace AppBundle\Form\IscrizioneAsiloNido;

use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use AppBundle\Entity\AsiloNido;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class SelezionaOrariNidoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $helper->setGuideText('iscrizione_asilo_nido.guida_alla_compilazione.seleziona_orari', true);

        /** @var AsiloNido $asilo */
        $asilo = $pratica = $builder->getData()->getStruttura();
        $orari = array_combine($asilo->getOrari(), $asilo->getOrari());

        $builder
            ->add('struttura_orario', ChoiceType::class, [
                "required" => true,
                "label" => 'iscrizione_asilo_nido.seleziona_orario',
                'expanded' => true,
                'choices' => $orari,
            ])
            ->add('periodo_iscrizione_da', DateType::class, [
                'required' => true,
                'label' => 'iscrizione_asilo_nido.periodoIscrizioneDa',
                'widget' => 'single_text',
                'format' => 'dd-MM-yyyy',
                'attr' => [
                    'class' => 'form-control input-inline datepicker-range-from',
                    'data-provide' => 'datepicker',
                    'data-date-format' => 'dd-mm-yyyy'
                ]
            ])
            ->add('periodo_iscrizione_a', DateType::class, [
                'required' => true,
                'label' => 'iscrizione_asilo_nido.periodoIscrizioneA',
                'widget' => 'single_text',
                'format' => 'dd-MM-yyyy',
                'attr' => [
                    'class' => 'form-control input-inline datepicker-range-to',
                    'data-provide' => 'datepicker',
                    'data-date-format' => 'dd-mm-yyyy'
                ]
            ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
    }

    /**
     * FormEvents::PRE_SUBMIT $listener
     *
     * @param FormEvent $event
     */
    public function onPreSubmit(FormEvent $event)
    {
        $data = $event->getData();
        if ( strtotime($data['periodo_iscrizione_da']) >= strtotime($data['periodo_iscrizione_a']))
        {
            $event->getForm()->addError(new FormError("La data di fine iscrizione deve essere maggiore di quella d'inizio"));
        }
    }

    public function getBlockPrefix()
    {
        return 'iscrizione_asilo_nido_orari';
    }
}
