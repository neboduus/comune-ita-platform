<?php

namespace AppBundle\Form\IscrizioneAsiloNido;

use AppBundle\Services\SdcDataProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;


class SelezionaNidoForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var SdcDataProvider $sdcDataProvider */
        $sdcDataProvider = $options['sdc_data'];
        $builder->add('asilo', ChoiceType::class, [
            'mapped' => false,
            'choices' => (array)$sdcDataProvider->get('asili'),
            'expanded' => false,
            'multiple' => false
        ]);
    }

    public function getBlockPrefix()
    {
        return 'iscrizione_asilo_nido_seleziona_nido';
    }
}