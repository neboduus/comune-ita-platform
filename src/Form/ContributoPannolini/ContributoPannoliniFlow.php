<?php

namespace App\Form\ContributoPannolini;

use App\Form\Base\AccettazioneIstruzioniType;
use App\Form\Base\AllegatiType;
use App\Form\Base\DatiContoCorrenteType;
use App\Form\Base\DatiRichiedenteType;
use App\Form\Base\PraticaFlow;
use App\Form\Base\SelezionaEnteType;
use App\Form\Base\SelectPaymentGatewayType;
use App\Form\Base\PaymentGatewayType;

//use App\Form\IscrizioneAsiloNido\DatiBambinoType;

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
        $steps =  array(
            self::STEP_ACCETTAZIONE_ISTRUZIONI => array(
                'label' => 'steps.common.accettazione_istruzioni.label',
                'form_type' => AccettazioneIstruzioniType::class,
            ),
            self::STEP_DATI_RICHIEDENTE => array(
                'label' => 'steps.common.dati_richiedente.label',
                'form_type' => DatiRichiedenteType::class,
            ),
            self::STEP_DATI_BAMBINO => array(
                'label' => 'steps.contributo_pannolini.dati_bambino.label',
                'form_type' => DatiBambinoType::class,
            ),
            self::STEP_DATI_ACQUISTO => array(
                'label' => 'steps.contributo_pannolini.dati_acquisto.label',
                'form_type' => DatiAcquistoType::class,
            ),
            self::STEP_ALLEGATI => array(
                'label' => 'steps.common.carica_allegati.label',
                'form_type' => AllegatiType::class,
            ),
            self::STEP_DATI_CONTO_CORRENTE => array(
                'label' => 'steps.contributo_pannolini.dati_conto_corrente.label',
                'form_type' => DatiContoCorrenteType::class,
            )
        );

        // Mostro lo step del'ente solo se è necesario
        if ($this->getFormData()->getEnte() == null && $this->prefix == null)
        {
            $steps [self::STEP_SELEZIONA_ENTE] = array(
                'label' => 'steps.common.seleziona_ente.label',
                'form_type' => SelezionaEnteType::class,
                'skip' => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
                    return ($flow->getFormData()->getEnte() != null && $this->prefix != null);
                }
            );
        }
        ksort($steps);

        // Attivo gli step di pagamento solo se è richiesto nel servizio
        if ($this->isPaymentRequired())
        {

            $steps[]= array(
                'label' => 'steps.common.select_payment_gateway.label',
                'form_type' => SelectPaymentGatewayType::class
            );
            $steps[]= array(
                'label' => 'steps.common.payment_gateway.label',
                'form_type' => PaymentGatewayType::class
            );
        }

        $steps[]= array(
            'label' => 'steps.common.conferma.label'
        );

        return $steps;

    }
}
