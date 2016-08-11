<?php

namespace AppBundle\Form\IscrizioneAsiloNido;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

class IstruzioniForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'accettazione_delle_istruzioni',
            CheckboxType::class,
            ["required" => true, "mapped" => false, "label" => 'iscrizione_asilo_nido.accettazione_istruzioni']
        );
    }

    public function getBlockPrefix()
    {
        return 'iscrizione_asilo_nido_istruzioni';
    }
}
