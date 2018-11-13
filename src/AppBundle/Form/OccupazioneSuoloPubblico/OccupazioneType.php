<?php

namespace AppBundle\Form\OccupazioneSuoloPubblico;

use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class OccupazioneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $helper->setGuideText('steps.occupazione_suolo_pubblico.guida_alla_compilazione', true);
        $helper->setStepTitle('steps.occupazione_suolo_pubblico.occupazione.title', true);
        $builder
            ->add('nomeIniziativa', TextType::class, ["label" => 'steps.occupazione_suolo_pubblico.occupazione.nomeIniziativa'])
            ->add('indirizzoOccupazione', TextType::class, ["label" => 'steps.occupazione_suolo_pubblico.occupazione.indirizzo'])
            ->add('civicoOccupazione', TextType::class, ["label" => 'steps.occupazione_suolo_pubblico.occupazione.civico'])
            ;
    }

    public function getBlockPrefix()
    {
        return 'occupazione_suolo_pubblico_occupazione';
    }
}
