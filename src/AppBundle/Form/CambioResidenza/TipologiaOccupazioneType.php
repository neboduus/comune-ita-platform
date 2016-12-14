<?php

namespace AppBundle\Form\CambioResidenza;

use AppBundle\Entity\CambioResidenza;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;

class TipologiaOccupazioneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $helper->setGuideText('steps.cambio_residenza.tipologia_occupazione.guida_alla_compilazione', true);
        $helper->setStepTitle('steps.cambio_residenza.tipologia_occupazione.title', true);

        /** @var CambioResidenza $pratica */
        $pratica = $builder->getData();
        $choices = array();
        foreach ($pratica->getTipiOccupazione() as $occupazione) {
            $choices[$helper->translate('steps.cambio_residenza.tipologia_occupazione.' . $occupazione)] = $occupazione;
        }

        $builder->add('tipoOccupazione', ChoiceType::class, [
            'choices' => $choices,
            'expanded' => true,
            'multiple' => false,
            'label' => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'cambio_residenza_tipologia_occupazione';
    }
}
