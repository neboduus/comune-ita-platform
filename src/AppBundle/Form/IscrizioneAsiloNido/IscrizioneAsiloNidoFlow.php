<?php

namespace AppBundle\Form\IscrizioneAsiloNido;

use AppBundle\Form\Base\AccettazioneIstruzioniType;
use AppBundle\Form\Base\DatiRichiedenteType;
use AppBundle\Form\Base\NucleoFamiliareType;
use AppBundle\Form\Base\PraticaFlow;
use AppBundle\Form\Base\SelezionaEnteType;
use AppBundle\Form\Base\SelectPaymentGatewayType;
use AppBundle\Form\Base\PaymentGatewayType;

class IscrizioneAsiloNidoFlow extends PraticaFlow
{

    const STEP_SELEZIONA_ENTE = 1;
    const STEP_ACCETTAZIONE_ISTRUZIONI = 2;
    const STEP_SELEZIONA_NIDO = 3;
    const STEP_ACCETTAZIONE_UTILIZZO_NIDO = 4;
    const STEP_SELEZIONA_ORARI_NIDO = 5;
    const STEP_DATI_RICHIEDENTE = 6;
    const STEP_DATI_BAMBINO = 7;
    const STEP_NUCLEO_FAMILIARE = 8;
    const STEP_ALLEGA_ATTESTAZIONE_ICEF = 9;
    const STEP_CONFERMA = 10;

    protected $allowDynamicStepNavigation = true;
    protected $handleFileUploads = false;


    protected function loadStepsConfig()
    {
        $steps =  array(
            self::STEP_SELEZIONA_ENTE => array(
                'label' => 'steps.common.seleziona_ente.label',
                'form_type' => SelezionaEnteType::class,
            ),
            self::STEP_ACCETTAZIONE_ISTRUZIONI => array(
                'label' => 'steps.common.accettazione_istruzioni.label',
                'form_type' => AccettazioneIstruzioniType::class,
            ),
            self::STEP_SELEZIONA_NIDO => array(
                'label' => 'steps.iscrizione_asilo_nido.seleziona_nido.label',
                'form_type' => SelezionaNidoType::class,
            ),
            self::STEP_ACCETTAZIONE_UTILIZZO_NIDO => array(
                'label' => 'steps.iscrizione_asilo_nido.accettazione_utilizzo.label',
                'form_type' => AccettazioneUtilizzoNidoType::class,
            ),
            self::STEP_SELEZIONA_ORARI_NIDO => array(
                'label' => 'steps.iscrizione_asilo_nido.seleziona_orari.label',
                'form_type' => SelezionaOrariNidoType::class,
            ),
            self::STEP_DATI_RICHIEDENTE => array(
                'label' => 'steps.common.dati_richiedente.label',
                'form_type' => DatiRichiedenteType::class,
            ),
            self::STEP_DATI_BAMBINO => array(
                'label' => 'steps.iscrizione_asilo_nido.dati_bambino.label',
                'form_type' => DatiBambinoType::class,
            ),
            self::STEP_NUCLEO_FAMILIARE => array(
                'label' => 'steps.common.nucleo_familiare.label',
                'form_type' => NucleoFamiliareType::class,
            ),
            self::STEP_ALLEGA_ATTESTAZIONE_ICEF => array(
                'label' => 'steps.iscrizione_asilo_nido.allega_attestazione_icef.label',
                'form_type' => AttestazioneIcefType::class,
            )
        );

        // Attivo gli step di pagamento solo se è richiesto nel servizio
        if ($this->isPaymentRequired())
        {
            $steps[count($steps) + 1] = array(
                'label' => 'steps.common.select_payment_gateway.label',
                'form_type' => SelectPaymentGatewayType::class
            );
            $steps[count($steps) + 1] = array(
                'label' => 'steps.common.payment_gateway.label',
                'form_type' => PaymentGatewayType::class
            );
        }

        $steps[count($steps) + 1] = array(
            'label' => 'steps.common.conferma.label'
        );

        return $steps;
    }
}
