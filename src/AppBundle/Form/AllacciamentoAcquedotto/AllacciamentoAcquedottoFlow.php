<?php
namespace AppBundle\Form\AllacciamentoAcquedotto;

use AppBundle\Form\Base\AccettazioneIstruzioniType;
use AppBundle\Form\Base\DatiRichiedenteType;
use AppBundle\Form\Base\PraticaFlow;
use AppBundle\Form\Base\SelezionaEnteType;

class AllacciamentoAcquedottoFlow extends PraticaFlow
{
    const STEP_SELEZIONA_ENTE = 1;
    const STEP_ACCETTAZIONE_ISTRUZIONI = 2;
    const STEP_DATI_RICHIEDENTE = 3;
    const STEP_DATI_IMMOBILE = 4;
    const STEP_DATI_INTERVENTO = 5;
    const STEP_DATI_COMUNICAZIONI = 6;
    const STEP_CONFERMA = 7;

    protected $allowDynamicStepNavigation = true;

    protected function loadStepsConfig()
    {
        return array(
            self::STEP_SELEZIONA_ENTE => array(
                'label' => 'steps.common.seleziona_ente.label',
                'form_type' => SelezionaEnteType::class,
            ),
            self::STEP_ACCETTAZIONE_ISTRUZIONI => array(
                'label' => 'steps.common.accettazione_istruzioni.label',
                'form_type' => AccettazioneIstruzioniType::class,
            ),
            self::STEP_DATI_RICHIEDENTE => array(
                'label' => 'steps.common.dati_richiedente.label',
                'form_type' => DatiRichiedenteType::class,
            ),
            self::STEP_DATI_IMMOBILE => array(
                'label' => 'steps.allacciamento_acquedotto.dati_immobile.label',
                'form_type' => DatiImmobileType::class,
            ),
            self::STEP_DATI_INTERVENTO => array(
                'label' => 'steps.allacciamento_acquedotto.dati_intervento.label',
                'form_type' => DatiInterventoType::class,
            ),
            self::STEP_DATI_COMUNICAZIONI => array(
                'label' => 'steps.allacciamento_acquedotto.dati_contatto.label',
                'form_type' => DatiContattoType::class,
            ),
            self::STEP_CONFERMA => array(
                'label' => 'steps.common.conferma.label',
            ),
        );
    }

}
