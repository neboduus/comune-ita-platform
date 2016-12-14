<?php

namespace AppBundle\Form\IscrizioneAsiloNido;

use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class DatiBambinoType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $helper->setGuideText('steps.iscrizione_asilo_nido.dati_bambino.guida_alla_compilazione', true);
        $helper->setStepTitle('steps.iscrizione_asilo_nido.dati_bambino.title', true);

        $builder
            ->add('bambino_nome', TextType::class, [
                'required' => true,
                 'label' => 'steps.iscrizione_asilo_nido.dati_bambino..nome',
            ])
            ->add('bambino_cognome', TextType::class, [
                'required' => true,
                'label' => 'steps.iscrizione_asilo_nido.dati_bambino..cognome',
            ])
            ->add('bambino_luogo_nascita', TextType::class, [
                'required' => true,
                'label' => 'steps.iscrizione_asilo_nido.dati_bambino..luogo_nascita',
            ])
            ->add('bambino_data_nascita', DateType::class, [
                'required' => true,
                'label' => 'steps.iscrizione_asilo_nido.dati_bambino..data_nascita',
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
        return 'iscrizione_asilo_nido_bambino';
    }
}
