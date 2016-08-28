<?php

namespace AppBundle\Form\IscrizioneAsiloNido;

use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use AppBundle\Entity\AsiloNido;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;

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
                'choices' => $orari,
            ])
            ->add('periodo_iscrizione_da', DateType::class, [
                'required' => true,
                'label' => 'iscrizione_asilo_nido.periodoIscrizioneDa',
                'widget' => 'single_text',
                'format' => 'dd-MM-yyyy',
                'attr' => [
                    'class' => 'form-control input-inline datepicker',
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
                    'class' => 'form-control input-inline datepicker',
                    'data-provide' => 'datepicker',
                    'data-date-format' => 'dd-mm-yyyy'
                ]
            ]);
    }

    public function getBlockPrefix()
    {
        return 'iscrizione_asilo_nido_orari';
    }
}
