<?php

namespace AppBundle\Form\CambioResidenza;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;

class InformazioneAccertamentoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];

        $helper->setGuideText('cambio_residenza.guida_alla_compilazione.informazioni_accertamento',
            true);
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
