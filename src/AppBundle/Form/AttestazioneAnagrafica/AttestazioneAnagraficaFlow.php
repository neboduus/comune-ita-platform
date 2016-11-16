<?php

namespace AppBundle\Form\AttestazioneAnagrafica;

use AppBundle\Form\Base\AccettazioneIstruzioniType;
use AppBundle\Form\Base\DatiRichiedenteType;
use AppBundle\Form\Base\PraticaFlow;
use AppBundle\Form\Base\SelezionaEnteType;

/**
 * Class AttestazioneAnagraficaFlow
 */
class AttestazioneAnagraficaFlow extends PraticaFlow
{
    const STEP_SELEZIONA_ENTE = 1;
    const STEP_ACCETTAZIONE_ISTRUZIONI = 2;
    const STEP_DATI_RICHIEDENTE = 3;
    const STEP_CONFERMA = 4;

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
            self::STEP_CONFERMA => array(
                'label' => 'pratica.conferma',
            ),
        );
    }
}
