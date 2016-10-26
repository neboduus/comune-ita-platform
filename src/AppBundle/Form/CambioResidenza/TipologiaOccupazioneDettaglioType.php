<?php

namespace AppBundle\Form\CambioResidenza;

use AppBundle\Entity\CambioResidenza;
use AppBundle\Form\Base\ChooseAllegatoType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;

class TipologiaOccupazioneDettaglioType extends AbstractType
{
    const OCCUPAZIONE_LOCAZIONE_ERP_FILE_DESCRIPTION = "Contratto o verbale di consegna immobile in locazione";
    const OCCUPAZIONE_AUTOCERTIFICAZIONE_FILE_DESCRIPTION = "Autocertificazione del proprietario dell'appartamento";

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];

        /** @var CambioResidenza $pratica */
        $pratica = $builder->getData();
        $occupazione = $pratica->getTipoOccupazione();

        switch ($occupazione) {
            case CambioResidenza::OCCUPAZIONE_PROPRIETARIO:
                $helper->setGuideText('cambio_residenza.guida_alla_compilazione.occupazione.proprietario',
                    true);
                $builder
                    ->add('proprietarioCatastoSezione', TextType::class, [
                        'label' => 'cambio_residenza.proprietarioCatastoSezione',
                        'required' => true,
                    ])
                    ->add('proprietarioCatastoFoglio', TextType::class, [
                        'label' => 'cambio_residenza.proprietarioCatastoFoglio',
                        'required' => true,
                    ])
                    ->add('proprietarioCatastoParticella', TextType::class, [
                        'label' => 'cambio_residenza.proprietarioCatastoParticella',
                        'required' => false,
                    ])
                    ->add('proprietarioCatastoSubalterno', TextType::class, [
                        'label' => 'cambio_residenza.proprietarioCatastoSubalterno',
                        'required' => false,
                    ]);
                break;

            case CambioResidenza::OCCUPAZIONE_LOCAZIONE:
                $helper->setGuideText('cambio_residenza.guida_alla_compilazione.occupazione.locazione',
                    true);
                $builder
                    ->add('contrattoAgenzia', TextType::class, [
                        'label' => 'cambio_residenza.contrattoAgenzia',
                        'required' => true,
                    ])
                    ->add('contrattoNumero', TextType::class, [
                        'label' => 'cambio_residenza.contrattoNumero',
                        'required' => true,
                    ])
                    ->add('contrattoData', DateType::class, [
                        'label' => 'cambio_residenza.contrattoData',
                        'required' => true,
                        'widget' => 'single_text',
                        'format' => 'dd-MM-yyyy',
                        'attr' => [
                            'class' => 'form-control input-inline datepicker',
                            'data-provide' => 'datepicker',
                            'data-date-format' => 'dd-mm-yyyy'
                        ]
                    ]);
                break;

            case CambioResidenza::OCCUPAZIONE_LOCAZIONE_ERP:
                $helper->setGuideText('cambio_residenza.guida_alla_compilazione.occupazione.locazione_erp',
                    true);
                $builder
                    ->add('verbaleConsegna', ChooseAllegatoType::class, [
                        'label' => 'cambio_residenza.verbaleConsegna',
                        'fileDescription' => self::OCCUPAZIONE_LOCAZIONE_ERP_FILE_DESCRIPTION,
                        'required' => true,
                        'pratica' => $builder->getData(),
                        'mapped' => false,
                    ]);
                break;

            case CambioResidenza::OCCUPAZIONE_COMODATO:
                $helper->setGuideText('cambio_residenza.guida_alla_compilazione.occupazione.comodato',
                    true);
                $builder
                    ->add('contrattoAgenzia', TextType::class, [
                        'label' => 'cambio_residenza.contrattoAgenzia',
                        'required' => true,
                    ])
                    ->add('contrattoNumero', TextType::class, [
                        'label' => 'cambio_residenza.contrattoNumero',
                        'required' => true,
                    ])
                    ->add('contrattoData', DateType::class, [
                        'label' => 'cambio_residenza.contrattoData',
                        'required' => true,
                        'widget' => 'single_text',
                        'format' => 'dd-MM-yyyy',
                        'attr' => [
                            'class' => 'form-control input-inline datepicker',
                            'data-provide' => 'datepicker',
                            'data-date-format' => 'dd-mm-yyyy'
                        ]
                    ]);
                break;

            case CambioResidenza::OCCUPAZIONE_USUFRUTTO:
                $helper->setGuideText('cambio_residenza.guida_alla_compilazione.occupazione.usufruttuario',
                    true);
                $builder
                    ->add('usufruttuarioInfo', TextareaType::class, [
                        'label' => 'cambio_residenza.usufruttuarioInfo',
                        'required' => true,
                    ]);
                break;

            case CambioResidenza::OCCUPAZIONE_AUTOCERTIFICAZIONE:
                $helper->setGuideText('cambio_residenza.guida_alla_compilazione.occupazione.autocertificazione',
                    true);
                $builder
                    ->add('autocertificazione', ChooseAllegatoType::class, [
                        'label' => 'cambio_residenza.autocertificazione',
                        'fileDescription' => self::OCCUPAZIONE_AUTOCERTIFICAZIONE_FILE_DESCRIPTION,
                        'required' => true,
                        'pratica' => $builder->getData(),
                        'mapped' => false,
                    ]);
                break;
        }
    }

    public function getBlockPrefix()
    {
        return 'cambio_residenza_tipologia_occupazione_dettaglio';
    }
}
