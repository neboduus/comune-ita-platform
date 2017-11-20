<?php

namespace AppBundle\Form\CertificatoNascita;

use AppBundle\Form\Base\AccettazioneIstruzioniType;
use AppBundle\Form\Base\CertificatoAnagraficoType;
use AppBundle\Form\Base\DatiRichiedenteType;
use AppBundle\Form\Base\DelegaType;
use AppBundle\Form\Base\PraticaFlow;
use AppBundle\Form\Base\SelectPaymentGatewayType;
use AppBundle\Form\Base\PaymentGatewayType;
use AppBundle\Form\Base\SelezionaEnteType;

/**
 * Class CertificatoNascitaFlow
 */
class CertificatoNascitaFlow extends PraticaFlow
{
    const STEP_SELEZIONA_ENTE = 1;
    const STEP_ACCETTAZIONE_ISTRUZIONI = 2;
    const STEP_DATI_RICHIEDENTE = 3;
    const STEP_DELEGA = 4;
    const STEP_CERTIFICATO_ANAGRAFICO = 5;
    const STEP_SELECT_PAYMENT_GATEWAY = 6;

    protected $allowDynamicStepNavigation = true;

    protected function loadStepsConfig()
    {

        $steps = array(
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
            self::STEP_DELEGA => array(
                'label' => 'steps.common.delega.label',
                'form_type' => DelegaType::class,
            ),
            self::STEP_CERTIFICATO_ANAGRAFICO => array(
                'label' => 'steps.common.certificato_anagrafico.label',
                'form_type' => CertificatoAnagraficoType::class,
            ),
            /*self::STEP_SELECT_PAYMENT_GATEWAY => array(
                'label' => 'steps.common.select_payment_gateway.label',
                'form_type' => SelectPaymentGatewayType::class,
            ),
            self::STEP_PAYMENT_GATEWAY => array(
                'label' => 'steps.common.payment_gateway.label',
                'form_type' => PaymentGatewayType::class,
            ),
            self::STEP_CONFERMA => array(
                'label' => 'steps.common.conferma.label',
            )*/
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
