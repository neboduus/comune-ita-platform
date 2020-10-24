<?php
namespace App\Form\AllacciamentoAcquedotto;

use App\Form\Base\AccettazioneIstruzioniType;
use App\Form\Base\DatiRichiedenteType;
use App\Form\Base\PraticaFlow;
use App\Form\Base\SelezionaEnteType;
use App\Form\Base\SelectPaymentGatewayType;
use App\Form\Base\PaymentGatewayType;
use Craue\FormFlowBundle\Form\FormFlowInterface;

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
        $steps =  array(
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
