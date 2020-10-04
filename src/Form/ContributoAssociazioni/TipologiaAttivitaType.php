<?php

namespace App\Form\ContributoAssociazioni;

use App\Entity\ContributoAssociazioni;
use App\Form\Extension\TestiAccompagnatoriProcedura;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;


class TipologiaAttivitaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $helper->setGuideText('steps.contributo_associazioni.tipologia_attivita.guida_alla_compilazione', true);
        $helper->setStepTitle('steps.contributo_associazioni.tipologia_attivita.title', true);

        /** @var ContributoAssociazioni $pratica */
        $pratica = $builder->getData();
        $tipologie = [];
        foreach ($pratica->getTipologieAttivita() as $type) {
            $tipologie[$helper->translate('steps.contributo_associazioni.tipologia_attivita.' . $type)] = $type;
        }

        $builder
            ->add('tipologiaAttivita', ChoiceType::class, [
                "label" => 'steps.contributo_associazioni.tipologia_attivita.tipologia',
                'expanded' => true,
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
        return 'contributo_associazioni_tipologia_attivita';
    }
}
