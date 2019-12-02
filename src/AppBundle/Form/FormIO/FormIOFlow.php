<?php
namespace AppBundle\Form\FormIO;

use AppBundle\Entity\Pratica;
use AppBundle\Form\Base\AccettazioneIstruzioniType;
use AppBundle\Form\Base\DatiRichiedenteType;
use AppBundle\Form\Base\PraticaFlow;
use AppBundle\Form\Base\SelezionaEnteType;
use Craue\FormFlowBundle\Form\FormFlowInterface;
use AppBundle\Form\Base\SelectPaymentGatewayType;
use AppBundle\Form\Base\PaymentGatewayType;

class FormIOFlow extends PraticaFlow
{
    /*const STEP_SELEZIONA_ENTE = 1;
    const STEP_ACCETTAZIONE_ISTRUZIONI = 2;*/
    const STEP_DATI_RICHIEDENTE = 1;
    const STEP_MODULO_FORMIO = 2;


    protected $allowDynamicStepNavigation = true;

    protected function loadStepsConfig()
    {

        $pratica = $this->getFormData();

        $steps =  array(
            /*self::STEP_ACCETTAZIONE_ISTRUZIONI => array(
                'label' => 'steps.common.accettazione_istruzioni.label',
                'form_type' => AccettazioneIstruzioniType::class
            ),
            self::STEP_DATI_RICHIEDENTE => array(
                'label' => 'steps.common.dati_richiedente.label',
                'form_type' => DatiRichiedenteType::class
            ),*/
            self::STEP_MODULO_FORMIO => array(
                'label' => 'steps.scia.modulo_default.label',
                'form_type' => FormIORenderType::class
            )
        );

        // Mostro lo step del'ente solo se è necesario
        if ($pratica->getEnte() == null && $this->prefix == null)
        {
            $steps [self::STEP_SELEZIONA_ENTE] = array(
                'label' => 'steps.common.seleziona_ente.label',
                'form_type' => SelezionaEnteType::class
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

        // Step conferma
        $steps[]= array(
            'label' => 'steps.common.conferma.label'
        );

        return $steps;
    }
}
