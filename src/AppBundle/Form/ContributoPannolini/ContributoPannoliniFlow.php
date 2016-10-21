<?php

namespace AppBundle\Form\ContributoPannolini;

use AppBundle\Form\Base\AccettazioneIstruzioniType;
use AppBundle\Form\Base\AllegatiType;
use AppBundle\Form\Base\DatiContoCorrenteType;
use AppBundle\Form\Base\DatiRichiedenteType;
use AppBundle\Form\Base\PraticaFlow;
use AppBundle\Form\Base\SelezionaEnteType;
//use AppBundle\Form\IscrizioneAsiloNido\DatiBambinoType;
use AppBundle\Form\ContributoPannolini\DatiBambinoType;

/**
 * Class ContributoPannoliniFlow
 */
class ContributoPannoliniFlow extends PraticaFlow
{
    const STEP_SELEZIONA_ENTE = 1;
    const STEP_ACCETTAZIONE_ISTRUZIONI = 2;
    const STEP_DATI_RICHIEDENTE = 3;
    const STEP_DATI_BAMBINO = 4;
    const STEP_DATI_ACQUISTO = 5;
    const STEP_ALLEGATI = 6;
    const STEP_DATI_CONTO_CORRENTE = 7;
    const STEP_CONFERMA = 8;

    protected $allowDynamicStepNavigation = true;

    protected function loadStepsConfig()
    {
        return array(
            self::STEP_SELEZIONA_ENTE => array(
                'label' => 'pratica.selezionaEnte',
                'form_type' => SelezionaEnteType::class,
            ),
            self::STEP_ACCETTAZIONE_ISTRUZIONI => array(
                'label' => 'pratica.accettazioneIstruzioni',
                'form_type' => AccettazioneIstruzioniType::class,
            ),
            self::STEP_DATI_RICHIEDENTE => array(
                'label' => 'pratica.datiRichiedente',
                'form_type' => DatiRichiedenteType::class,
            ),
            self::STEP_DATI_BAMBINO => array(
                'label' => 'contributo_pannolini.datiBambino',
                'form_type' => DatiBambinoType::class,
            ),
            self::STEP_DATI_ACQUISTO => array(
                'label' => 'contributo_pannolini.datiAcquisto',
                'form_type' => DatiAcquistoType::class,
            ),
            self::STEP_ALLEGATI => array(
                'label' => 'pratica.carica_allegati',
                'form_type' => AllegatiType::class,
            ),
            self::STEP_DATI_CONTO_CORRENTE => array(
                'label' => 'contributo_pannolini.datiContoCorrente',
                'form_type' => DatiContoCorrenteType::class,
            ),
            self::STEP_CONFERMA => array(
                'label' => 'pratica.conferma',
            ),
        );
    }
}
