<?php

namespace AppBundle\Form\IscrizioneAsiloNido;

use AppBundle\Entity\IscrizioneAsiloNido;
use AppBundle\Form\Base\ChooseAllegatoType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;


class AttestazioneIcefType extends AbstractType
{
    const ATTESTAZIONE_ICEF_FILE_DESCRIPTION = 'Attestazione ICEF per i servizi alla prima infanzia';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];

        $helper->setGuideText('steps.iscrizione_asilo_nido.allega_attestazione_icef.guida_alla_compilazione',
            true);
        $builder
            ->add('autocertificazione', ChooseAllegatoType::class, [
                'label' => false,
                'fileDescription' => self::ATTESTAZIONE_ICEF_FILE_DESCRIPTION,
                'required' => false,
                'pratica' => $builder->getData(),
                'mapped' => false,
            ]);
    }
}