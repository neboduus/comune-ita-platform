<?php

namespace App\Form\Base;

use App\Entity\Pratica;
use App\Form\Extension\TestiAccompagnatoriProcedura;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

class AccettazioneIstruzioniType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Pratica $pratica */
        $pratica = $builder->getData();

        $servizio = $pratica->getServizio();

        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $helper->setDescriptionText(
            $servizio->getHowto(),
            true,
            ['%comune%' => $pratica->getEnte()->getName()]
        );
        $helper->setStepTitle('steps.common.accettazione_istruzioni.title', true);

        $helper->setGuideText('steps.common.accettazione_istruzioni.guida_alla_compilazione', true);

        $builder->add(
            'accetto_istruzioni',
            CheckboxType::class,
            [
                "required" => true,
                "label" => 'steps.common.accettazione_istruzioni.accetto_istruzioni',
            ]
        );
    }

    public function getBlockPrefix()
    {
        return 'pratica_accettazione_istruzioni';
    }
}
