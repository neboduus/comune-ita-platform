<?php

namespace AppBundle\Form\IscrizioneAsiloNido;

use AppBundle\Form\Base\AccettazioneIstruzioniType;
use AppBundle\Form\Base\NucleoFamiliareType;
use AppBundle\Form\Base\DatiRichiedenteType;
use AppBundle\Form\Base\SelezionaEnteType;
use AppBundle\Form\Base\AllegatiType;
use AppBundle\Form\Base\PraticaFlow;

class IscrizioneAsiloNidoFlow extends PraticaFlow
{

    const STEP_ACCETTAZIONE_ISTRUZIONI = 1;
    const STEP_SELEZIONA_ENTE = 2;
    const STEP_SELEZIONA_NIDO = 3;
    const STEP_ACCETTAZIONE_UTILIZZO_NIDO = 4;
    const STEP_SELEZIONA_ORARI_NIDO = 5;
    const STEP_DATI_RICHIEDENTE = 6;
    const STEP_DATI_BAMBINO = 7;
    const STEP_NUCLEO_FAMILIARE = 8;
    const STEP_ALLEGATI = 9;
    const STEP_CONFERMA = 10;

    protected $allowDynamicStepNavigation = true;
    protected $handleFileUploads = false;


    protected function loadStepsConfig()
    {
        return array(
            self::STEP_ACCETTAZIONE_ISTRUZIONI => array(
                'label' => 'iscrizione_asilo_nido.accettazioneIstruzioni',
                'form_type' => AccettazioneIstruzioniType::class,
            ),
            self::STEP_SELEZIONA_ENTE => array(
                'label' => 'iscrizione_asilo_nido.selezionaEnte',
                'form_type' => SelezionaEnteType::class,
            ),
            self::STEP_SELEZIONA_NIDO => array(
                'label' => 'iscrizione_asilo_nido.selezionaNido',
                'form_type' => SelezionaNidoType::class,
            ),
            self::STEP_ACCETTAZIONE_UTILIZZO_NIDO => array(
                'label' => 'iscrizione_asilo_nido.accettazioneUtilizzoNidoForm',
                'form_type' => AccettazioneUtilizzoNidoType::class,
            ),
            self::STEP_SELEZIONA_ORARI_NIDO => array(
                'label' => 'iscrizione_asilo_nido.selezionaOrariNido',
                'form_type' => SelezionaOrariNidoType::class,
            ),
            self::STEP_DATI_RICHIEDENTE => array(
                'label' => 'pratica.carica_datiRichiedente',
                'form_type' => DatiRichiedenteType::class,
            ),
            self::STEP_DATI_BAMBINO => array(
                'label' => 'iscrizione_asilo_nido.datiBambino',
                'form_type' => DatiBambinoType::class,
            ),
            self::STEP_NUCLEO_FAMILIARE => array(
                'label' => 'iscrizione_asilo_nido.nucleoFamiliare',
                'form_type' => NucleoFamiliareType::class,
            ),
            self::STEP_ALLEGATI => array(
                'label' => 'pratica.carica_allegati',
                'form_type' => AllegatiType::class,
            ),
            self::STEP_CONFERMA => array(
                'label' => 'iscrizione_asilo_nido.conferma',
            ),
        );
    }
}
