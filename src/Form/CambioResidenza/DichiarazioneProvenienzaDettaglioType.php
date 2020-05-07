<?php

namespace App\Form\CambioResidenza;

use App\Entity\CambioResidenza;
use App\Form\Extension\TestiAccompagnatoriProcedura;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class DichiarazioneProvenienzaDettaglioType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];

        /** @var CambioResidenza $pratica */
        $pratica = $builder->getData();
        $provenienza = $pratica->getProvenienza();

        $helper->setStepTitle('steps.cambio_residenza.dichiarazione_provenienza_dettaglio.title', true);

        switch ($provenienza) {
            case CambioResidenza::PROVENIENZA_ALTRO_COMUNE:

                $helper->setGuideText(
                    'steps.cambio_residenza.dichiarazione_provenienza_dettaglio.guida_alla_compilazione.altro_comune',
                    true
                );

                $builder->add('comuneDiProvenienza', TextType::class, [
                    'required' => true,
                ]);

                break;

            case CambioResidenza::PROVENIENZA_ESTERO:

                $helper->setGuideText(
                    'steps.cambio_residenza.dichiarazione_provenienza_dettaglio.guida_alla_compilazione.stato_estero',
                    true
                );

                $builder
                    ->add('statoEsteroDiProvenienza', TextType::class, [
                        'required' => true,
                    ]);
                break;

            case CambioResidenza::PROVENIENZA_AIRE:

                $helper->setGuideText(
                    'steps.cambio_residenza.dichiarazione_provenienza_dettaglio.guida_alla_compilazione.aire',
                    true
                );

                $builder
                    ->add('statoEsteroDiProvenienza', TextType::class, [
                        'required' => true,
                    ])
                    ->add('comuneEsteroDiProvenienza', TextType::class, [
                        'required' => true,
                    ]);

                break;

            case CambioResidenza::PROVENIENZA_ALTRO:

                $helper->setGuideText(
                    'steps.cambio_residenza.dichiarazione_provenienza_dettaglio.guida_alla_compilazione.altro_motivo',
                    true
                );

                $builder->add('altraProvenienza', TextareaType::class, [
                    'required' => true,
                ]);

                break;

            default:
                $helper->setDescriptionText(
                    'steps.cambio_residenza.dichiarazione_provenienza_dettaglio.guida_alla_compilazione.nessun_allegato',
                    true
                );
        }
    }

    public function getBlockPrefix()
    {
        return 'cambio_residenza_dichiarazione_provenienza_dettaglio';
    }
}
