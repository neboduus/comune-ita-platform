<?php

namespace AppBundle\Form\CambioResidenza;

use AppBundle\Entity\CambioResidenza;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;

class DichiarazioneProvenienzaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $helper->setGuideText('cambio_residenza.guida_alla_compilazione.dichiarazione_provenienza', true);

        /** @var CambioResidenza $pratica */
        $pratica = $builder->getData();
        $choices = array();
        foreach ($pratica->getTipiProvenienza() as $provenienza) {
            $choices[$helper->translate('cambio_residenza.provenienza.' . $provenienza)] = $provenienza;
        }

        $builder->add('provenienza', ChoiceType::class, [
            'choices' => $choices,
            'expanded' => false,
            'multiple' => false,
            'label' => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'cambio_residenza_dichiarazione_provenienza';
    }
}
