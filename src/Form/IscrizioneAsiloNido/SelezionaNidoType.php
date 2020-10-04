<?php

namespace App\Form\IscrizioneAsiloNido;

use App\Form\Extension\TestiAccompagnatoriProcedura;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SelezionaNidoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $helper->setGuideText('steps.iscrizione_asilo_nido.seleziona_nido.guida_alla_compilazione', true);
        $helper->setStepTitle('steps.iscrizione_asilo_nido.seleziona_nido.title', true);

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
