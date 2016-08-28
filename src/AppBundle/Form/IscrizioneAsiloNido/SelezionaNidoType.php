<?php

namespace AppBundle\Form\IscrizioneAsiloNido;

use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use AppBundle\Entity\AsiloNido;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SelezionaNidoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $helper->setGuideText('iscrizione_asilo_nido.guida_alla_compilazione.seleziona_nido', true);

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
