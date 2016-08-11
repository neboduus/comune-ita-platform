<?php

namespace AppBundle\Form\IscrizioneAsiloNido;

use AppBundle\Entity\AsiloNido;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SelezionaNidoForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('struttura', EntityType::class, [
            'class' => 'AppBundle\Entity\AsiloNido',
            'choices' => $builder->getData()->getEnte()->getAsili(),
            'choice_label' => 'name',
            'expanded' => false,
            'multiple' => false
        ]);
    }

    public function getBlockPrefix()
    {
        return 'iscrizione_asilo_nido_seleziona_nido';
    }
}
