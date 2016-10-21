<?php

namespace AppBundle\Form\ContributoPannolini;

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
        $helper->setGuideText('contributo_pannolini.guida_alla_compilazione.dati_bambino', true);

        $builder
            ->add('bambino_nome', TextType::class, [
                'required' => true,
                 'label' => 'iscrizione_asilo_nido.datiBambino.nome',
            ])
            ->add('bambino_cognome', TextType::class, [
                'required' => true,
                'label' => 'iscrizione_asilo_nido.datiBambino.cognome',
            ])
            ->add('bambino_luogo_nascita', TextType::class, [
                'required' => true,
                'label' => 'iscrizione_asilo_nido.datiBambino.luogo_nascita',
            ])
            ->add('bambino_data_nascita', DateType::class, [
                'required' => true,
                'label' => 'iscrizione_asilo_nido.datiBambino.data_nascita',
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
