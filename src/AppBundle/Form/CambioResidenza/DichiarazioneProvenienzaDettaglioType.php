<?php

namespace AppBundle\Form\CambioResidenza;

use AppBundle\Entity\CambioResidenza;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;


class DichiarazioneProvenienzaDettaglioType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];

        /** @var CambioResidenza $pratica */
        $pratica = $builder->getData();
        $provenienza = $pratica->getProvenienza();

        switch ($provenienza) {
            case CambioResidenza::PROVENIENZA_ALTRO_COMUNE:

                $helper->setGuideText('cambio_residenza.guida_alla_compilazione.dichiarazione_provenienza_altro_comune',
                    true);

                $builder->add('comuneDiProvenienza', TextType::class, [
                    'required' => true,
                ]);

                break;

            case CambioResidenza::PROVENIENZA_ESTERO:

                $helper->setGuideText('cambio_residenza.guida_alla_compilazione.dichiarazione_provenienza_stato_estero',
                    true);

                $builder
                    ->add('statoEsteroDiProvenienza', TextType::class, [
                        'required' => true,
                    ]);
                break;

            case CambioResidenza::PROVENIENZA_AIRE:

                $helper->setGuideText('cambio_residenza.guida_alla_compilazione.dichiarazione_provenienza_aire',
                    true);

                $builder
                    ->add('statoEsteroDiProvenienza', TextType::class, [
                        'required' => true,
                    ])
                    ->add('comuneEsteroDiProvenienza', TextType::class, [
                        'required' => true,
                    ]);

                break;

            case CambioResidenza::PROVENIENZA_ALTRO:

                $helper->setGuideText('cambio_residenza.guida_alla_compilazione.dichiarazione_provenienza_altro_motivo',
                    true);

                $builder->add('altraProvenienza', TextareaType::class, [
                    'required' => true,
                ]);

                break;

            default:
                $helper->setDescriptionText('cambio_residenza.guida_alla_compilazione.dichiarazione_provenienza_nessun_allegato',
                    true);
        }
    }

    public function getBlockPrefix()
    {
        return 'cambio_residenza_dichiarazione_provenienza_dettaglio';
    }
}
