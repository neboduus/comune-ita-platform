<?php

namespace AppBundle\Form\Base;

use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;


class AccettazioneIstruzioniType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $helper->setDescriptionText(
            '<div class="well well-sm service-disclaimer">' .
            $builder->getData()->getServizio()->getTestoIstruzioni() .
            '</div>'
        );

        $helper->setGuideText('pratica.guida_alla_compilazione.accettazione_istruzioni', true);

        $builder->add(
            'accetto_istruzioni',
            CheckboxType::class,
            [
                "required" => true,
                "label" => 'pratica.accetto_istruzioni',
            ]
        );
    }

    public function getBlockPrefix()
    {
        return 'pratica_accettazione_istruzioni';
    }

}
