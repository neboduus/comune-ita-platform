<?php

namespace App\Form\OccupazioneSuoloPubblico;

use App\Entity\OccupazioneSuoloPubblico;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;


class TipologiaOccupazioneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var OccupazioneSuoloPubblico $pratica */
        $pratica = $builder->getData();
        $tipologie = [];
        foreach ($pratica->getTipologieOccupazione() as $type) {
            $tipologie[$type] = $type;
        }

        $builder
            ->add('tipologiaOccupazione', ChoiceType::class, [
                "label" => 'steps.occupazione_suolo_pubblico.tipologia_occupazione.tipologia',
                'expanded' => false,
                'choices' => $tipologie,
            ]);
    }

    /**
     * FormEvents::PRE_SUBMIT $listener
     *
     * @param FormEvent $event
     */


    public function getBlockPrefix()
    {
        return 'occupazione_suolo_pubblico_tipologia_occupazione';
    }


}
