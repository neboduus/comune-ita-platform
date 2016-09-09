<?php

namespace AppBundle\Form\IscrizioneAsiloNido;

use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use AppBundle\Entity\AsiloNido;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;


class AccettazioneUtilizzoNidoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        /** @var AsiloNido $asilo */
        $asilo = $pratica = $builder->getData()->getStruttura();

        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $helper->setDescriptionText(
            '<div class="well well-sm service-disclaimer">' .
            $asilo->getSchedaInformativa() .
            '</div>'
        );

        $helper->setGuideText('iscrizione_asilo_nido.guida_alla_compilazione.accettazione_utilizzo_nido', true);

        $builder->add(
            'accetto_utilizzo',
            CheckboxType::class,
            ["required" => true, "label" => 'iscrizione_asilo_nido.accettazione_utilizzo_nido']
        );
    }

    public function getBlockPrefix()
    {
        return 'iscrizione_asilo_nido_utilizzo_nido';
    }
}
