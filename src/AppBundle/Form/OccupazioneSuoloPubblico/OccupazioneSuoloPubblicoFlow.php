<?php
namespace AppBundle\Form\OccupazioneSuoloPubblico;

use AppBundle\Entity\OccupazioneSuoloPubblico;
use AppBundle\Form\Base\AccettazioneIstruzioniType;
use AppBundle\Form\Base\DatiRichiedenteType;
use AppBundle\Form\Base\PraticaFlow;
use AppBundle\Form\Base\SelezionaEnteType;
use Craue\FormFlowBundle\Form\FormFlowInterface;
use AppBundle\Form\Base\SelectPaymentGatewayType;
use AppBundle\Form\Base\PaymentGatewayType;

/**
 * Class OccupazioneSuoloPubblicoFlow
 */
class OccupazioneSuoloPubblicoFlow extends PraticaFlow
{
    const STEP_SELEZIONA_ENTE = 1;
    const STEP_ACCETTAZIONE_ISTRUZIONI = 2;
    const STEP_DATI_RICHIEDENTE = 3;
    const STEP_DATI_ORG_RICHIEDENTE = 4;
    const STEP_OCCUPAZIONE = 5;
    const STEP_TIPO_OCCUPAZIONE = 6;
    const STEP_TEMPO_OCCUPAZIONE = 7;
    const STEP_ALLEGATI = 8;
    const STEP_IMPORTO = 9;
    const STEP_CONFERMA = 10;

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
                'label' => 'steps.occupazione_suolo_pubblico.org_richiedente.label',
                'form_type' => OrgRichiedenteType::class
            ),
            self::STEP_OCCUPAZIONE => array(
                'label' => 'steps.occupazione_suolo_pubblico.occupazione.label',
                'form_type' => OccupazioneType::class
            ),
            self::STEP_TEMPO_OCCUPAZIONE => array(
                'label' => 'steps.occupazione_suolo_pubblico.tempo_occupazione.label',
                'form_type' => TempoOccupazioneType::class,
                'skip' => function($estimatedCurrentStepNumber, FormFlowInterface $flow) {
                    return $flow->getFormData()->getTipologiaOccupazione() == OccupazioneSuoloPubblico::TIPOLOGIA_PERMANENTE;
                }
            ),
            self::STEP_ALLEGATI => array(
                'label' => 'steps.occupazione_suolo_pubblico.allegati.label',
                'form_type' => AllegatiType::class
            ),
        );


        if(!$this->isPaymentRequired()) {
            $steps[self::STEP_TIPO_OCCUPAZIONE] = array(
                'label' => 'steps.occupazione_suolo_pubblico.tipologia_occupazione.label',
                'form_type' => TipologiaOccupazioneType::class
            );
        } else {
            $steps[self::STEP_IMPORTO] = array(
                'label' => 'steps.common.inserisci_importo.label',
                'form_type' => ImportoType::class,
            );
        }

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
