<?php

namespace App\Form\IscrizioneRegistroAssociazioni;

use App\Form\Base\ChooseAllegatoType;
use App\Form\Base\UploadAllegatoType;
use App\Form\Extension\TestiAccompagnatoriProcedura;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;


class AllegatiType extends AbstractType
{
    const ELENCO_DELLE_CARICHE_SOCIALI = 'Elenco delle cariche sociali';
    const STATUTO = 'Statuto';
    const ATTRIBUZIONE_CODICE_FISCALE = 'Attribuzione codice fiscale';
    const LOGO = 'Logo';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];

        $helper->setGuideText('steps.iscrizione_registro_associazioni.allegati.guida_alla_compilazione',
            true);
        $builder
            ->add('elenco_cariche_sociali', UploadAllegatoType::class, [
                'label' => 'Carica il file con l\'elenco delle cariche sociali.',
                'fileDescription' => self::ELENCO_DELLE_CARICHE_SOCIALI,
                'required' => true,
                'pratica' => $builder->getData(),
                'mapped' => false,
            ])
            ->add('statuto', UploadAllegatoType::class, [
                'label' => 'Carica il file con lo statuto.',
                'fileDescription' => self::STATUTO,
                'required' => true,
                'pratica' => $builder->getData(),
                'mapped' => false,
            ])
            ->add('attribuzione_codice_fiscale', UploadAllegatoType::class, [
                'label' => 'Carica il file con l\'attribuzione del codice fiscale.',
                'fileDescription' => self::ATTRIBUZIONE_CODICE_FISCALE,
                'required' => true,
                'pratica' => $builder->getData(),
                'mapped' => false,
            ])
            ->add('logo', UploadAllegatoType::class, [
                'label' => 'Carica il file con il logo (formato jpg).',

                'fileDescription' => self::LOGO,
                'required' => false,
                'pratica' => $builder->getData(),
                'mapped' => false,
            ])
            ;
    }
}
