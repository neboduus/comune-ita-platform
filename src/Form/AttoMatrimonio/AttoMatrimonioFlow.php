<?php

namespace App\Form\AttoMatrimonio;

use App\Form\Base\AccettazioneIstruzioniType;
use App\Form\Base\CertificatoAnagraficoType;
use App\Form\Base\DatiRichiedenteType;
use Craue\FormFlowBundle\Form\FormFlowInterface;
use App\Form\Base\PraticaFlow;
use App\Form\Base\SelectPaymentGatewayType;
use App\Form\Base\PaymentGatewayType;
use App\Form\Base\SelezionaEnteType;

/**
 * Class AttoMatrimonioFlow
 */
class AttoMatrimonioFlow extends PraticaFlow
{
    const STEP_SELEZIONA_ENTE = 1;
    const STEP_ACCETTAZIONE_ISTRUZIONI = 2;
    const STEP_DATI_RICHIEDENTE = 3;
    const STEP_DATI_ATTO = 4;
    const STEP_CERTIFICATO_ANAGRAFICO = 5;
    const STEP_SELECT_PAYMENT_GATEWAY = 8;

    protected $allowDynamicStepNavigation = true;

    protected function loadStepsConfig()
    {
        $steps = array(
            self::STEP_ACCETTAZIONE_ISTRUZIONI => array(
                'label' => 'steps.common.accettazione_istruzioni.label',
                'form_type' => AccettazioneIstruzioniType::class,
            ),
            self::STEP_DATI_RICHIEDENTE => array(
                'label' => 'steps.common.dati_richiedente.label',
                'form_type' => DatiRichiedenteType::class,
            ),
            self::STEP_DATI_ATTO => array(
                'label' => 'steps.certificato_matrimonio.dati_atto.label',
                'form_type' => DatiAttoType::class,
            ),
            self::STEP_CERTIFICATO_ANAGRAFICO => array(
                'label' => 'steps.common.certificato_anagrafico.AttoMatrimonio.label',
                'form_type' => CertificatoAnagraficoType::class,
            ),
        );

        // Mostro lo step del'ente solo se è necesario
        if ($this->getFormData()->getEnte() == null && $this->prefix == null) {
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
        if ($this->isPaymentRequired()) {
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
