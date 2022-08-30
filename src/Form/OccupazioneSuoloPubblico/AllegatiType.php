<?php

namespace App\Form\OccupazioneSuoloPubblico;

use App\Form\Base\ChooseAllegatoType;
use App\Form\Base\UploadAllegatoType;
use App\Form\Extension\TestiAccompagnatoriProcedura;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;


class AllegatiType extends AbstractType
{
    const OCCUPAZIONE_DIVIETO_UTILIZZO = 'Occupazione suolo pubblico <br/>
Divieto di transito-sosta <br/>
Utilizzo di attrezzature e/o servizi igienici <br/>';
    const DEROGA_UTILIZZO_IMPIANTI_SUONO = 'Deroga per utilizzo di impianti di diffusione del suono';
    const PATROCINIO = 'Patrocinio del Comune';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var TestiAccompagnatoriProcedura $helper */
        $helper = $options["helper"];

        $helper->setGuideText('steps.occupazione_suolo_pubblico.allegati.guida_alla_compilazione',
            true);
        $builder
            ->add('occupazione_divieto_utilizzo', UploadAllegatoType::class, [
                'label' => 'Carica il file con la dichiarazione di Occupazione suolo pubblico / Divieto di transito-sosta / Utilizzo di attrezzature e/o servizi igienici (opzionale)',
                'fileDescription' => self::OCCUPAZIONE_DIVIETO_UTILIZZO,
                'required' => false,
                'pratica' => $builder->getData(),
                'mapped' => false,
            ])
            ->add('deroga_utilizzo_impianti_suono', UploadAllegatoType::class, [
                'label' => 'Carica il file con la richiesta di deroga per utilizzo di impianti di diffusione del suono  (opzionale).',
                'fileDescription' => self::DEROGA_UTILIZZO_IMPIANTI_SUONO,
                'required' => false,
                'pratica' => $builder->getData(),
                'mapped' => false,
            ])
            ->add('patrocinio', UploadAllegatoType::class, [
                'label' => 'Carica il file con il patrocinio del comune (opzionale).',
                'fileDescription' => self::PATROCINIO,
                'required' => false,
                'pratica' => $builder->getData(),
                'mapped' => false,
            ])
            ;
    }
}
