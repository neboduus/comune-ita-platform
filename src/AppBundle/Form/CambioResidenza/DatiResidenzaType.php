<?php

namespace AppBundle\Form\CambioResidenza;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;


class DatiResidenzaType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];
        $helper->setGuideText('cambio_residenza.guida_alla_compilazione.dati_residenza', true);

        $builder
            ->add('residenzaProvincia', TextType::class, [
                'required' => true,
                'label' => 'cambio_residenza.datiResidenza.provincia',
            ])
            ->add('residenzaComune', TextType::class, [
                'required' => true,
                'label' => 'cambio_residenza.datiResidenza.comune',
            ])
            ->add('residenzaIndirizzo', TextType::class, [
                'required' => true,
                'label' => 'cambio_residenza.datiResidenza.indirizzo',
            ])
            ->add('residenzaNumeroCivico', TextType::class, [
                'required' => true,
                'label' => 'cambio_residenza.datiResidenza.numero_civico',
            ])
            ->add('residenzaScala', TextType::class, [
                'required' => false,
                'label' => 'cambio_residenza.datiResidenza.scala',
            ])
            ->add('residenzaPiano', TextType::class, [
                'required' => false,
                'label' => 'cambio_residenza.datiResidenza.piano',
            ])
            ->add('residenzaInterno', TextType::class, [
                'required' => false,
                'label' => 'cambio_residenza.datiResidenza.interno',
            ]);
    }

    public function getBlockPrefix()
    {
        return 'cambio_residenza_dati_residenza';
    }
}
