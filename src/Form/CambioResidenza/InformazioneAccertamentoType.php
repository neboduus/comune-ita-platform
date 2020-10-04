<?php

namespace App\Form\CambioResidenza;

use App\Form\Extension\TestiAccompagnatoriProcedura;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class InformazioneAccertamentoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];

        $helper->setGuideText('steps.cambio_residenza.informazioni_accertamento.guida_alla_compilazione', true);
        $helper->setStepTitle('steps.cambio_residenza.informazioni_accertamento.title', true);
        $builder
            ->add('infoAccertamento', TextareaType::class, [
                'label' => false,
                'required' => false,
            ]);
    }

    public function getBlockPrefix()
    {
        return 'cambio_residenza_informazioni_accertamento';
    }
}
