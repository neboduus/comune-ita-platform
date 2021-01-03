<?php
namespace App\Form\ContributoAssociazioni;

use App\Form\Base\AccettazioneIstruzioniType;
use App\Form\Base\DatiContoCorrenteType;
use App\Form\Base\DatiRichiedenteType;
use App\Form\Base\PraticaFlow;
use App\Form\Base\SelezionaEnteType;
use App\Form\Base\SelectPaymentGatewayType;
use App\Form\Base\PaymentGatewayType;
use Craue\FormFlowBundle\Form\FormFlowInterface;

/**
 * Class ContributoAssociazioniFlow
 */
class ContributoAssociazioniFlow extends PraticaFlow
{
    const STEP_SELEZIONA_ENTE = 1;
    const STEP_ACCETTAZIONE_ISTRUZIONI = 2;
    const STEP_DATI_RICHIEDENTE = 3;
    const STEP_DATI_ORG_RICHIEDENTE = 4;
    const STEP_TIPO_ATTIVITA = 5;
    const STEP_CONTRIBUTO = 6;
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
            self::STEP_DATI_ORG_RICHIEDENTE => array(
                'label' => 'steps.common.org_richiedente.label',
                'form_type' => OrgRichiedenteType::class
            ),
            self::STEP_TIPO_ATTIVITA => array(
                'label' => 'steps.contributo_associazioni.tipologia_attivita.label',
                'form_type' => TipologiaAttivitaType::class
            ),
            self::STEP_CONTRIBUTO => array(
                'label' => 'steps.contributo_associazioni.contributo.label',
                'form_type' => ContributoType::class
            ),
            self::STEP_DATI_CONTO_CORRENTE => array(
                'label' => 'steps.common.dati_conto_corrente.label',
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