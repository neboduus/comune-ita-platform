<?php

namespace AppBundle\Form\IscrizioneAsiloNido;

use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use AppBundle\Entity\AsiloNido;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;


class AccettazioneUtilizzoNidoForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        /** @var AsiloNido $asilo */
        $asilo = $pratica = $builder->getData()->getStruttura();

        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $helper->setDescriptionText(
            $asilo->getSchedaInformativa()
        );

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
