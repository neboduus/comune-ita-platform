<?php

namespace App\Form\FormIO;

use App\Entity\Pratica;
use App\Form\Base\PaymentGatewayType;
use App\Form\Base\PraticaFlow;
use App\Form\Base\SelectPaymentGatewayType;
use App\Form\Base\SelezionaEnteType;
use App\Form\Base\SummaryType;
use Craue\FormFlowBundle\Form\FormFlowInterface;

class FormIOFlow extends PraticaFlow
{
    const STEP_SELEZIONA_ENTE = 0;
    const STEP_DATI_RICHIEDENTE = 1;
    const STEP_MODULO_FORMIO = 2;


    protected $allowDynamicStepNavigation = true;

    protected function loadStepsConfig()
    {
        $pratica = $this->getFormData();

        $steps = array(
            self::STEP_MODULO_FORMIO => array(
                'label' => 'steps.scia.modulo_default.label',
                'form_type' => FormIORenderType::class,
                'skip' => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
                    return $flow->getFormData()->getStatus() != Pratica::STATUS_DRAFT;
                },
            )
        );

        // Mostro lo step del'ente solo se è necesario
        if ($pratica->getEnte() == null && $this->prefix == null) {
            $steps [self::STEP_SELEZIONA_ENTE] = array(
                'label' => 'steps.common.seleziona_ente.label',
                'form_type' => SelezionaEnteType::class
            );
        }
        ksort($steps);

        // Attivo gli step di pagamento solo se è richiesto nel servizio
        if ($this->isPaymentRequired()) {
            $steps[] = array(
                'label' => 'steps.common.conferma.label',
                'form_type' => SummaryType::class,
                'skip' => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
                    return $flow->getFormData()->getStatus() == Pratica::STATUS_PAYMENT_PENDING;
                },
            );
            $steps[] = array(
                'label' => 'steps.common.select_payment_gateway.label',
                'form_type' => SelectPaymentGatewayType::class,
                'skip' => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
                    return $flow->getFormData()->getStatus() == Pratica::STATUS_PAYMENT_PENDING;
                },
            );
            $steps[] = array(
                'label' => 'steps.common.payment_gateway.label',
                'form_type' => PaymentGatewayType::class
            );
            $steps[] = array(
                'label' => 'Verifica pagamento'
            );
        } else {
            // Step conferma
            $steps[] = array(
                'label' => 'steps.common.conferma.label'
            );
        }

        return $steps;
    }
}
