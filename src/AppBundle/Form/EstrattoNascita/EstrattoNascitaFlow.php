<?php

namespace AppBundle\Form\EstrattoNascita;

use AppBundle\Form\Base\AccettazioneIstruzioniType;
use AppBundle\Form\Base\CertificatoAnagraficoType;
use AppBundle\Form\Base\DatiRichiedenteType;
use AppBundle\Form\Base\DelegaType;
use AppBundle\Form\Base\SpecificaDelegaType;
use Craue\FormFlowBundle\Form\FormFlowInterface;
use AppBundle\Form\Base\PraticaFlow;
use AppBundle\Form\Base\SelectPaymentGatewayType;
use AppBundle\Form\Base\PaymentGatewayType;
use AppBundle\Form\Base\SelezionaEnteType;

/**
 * Class EstrattoNascitaFlow
 */
class EstrattoNascitaFlow extends PraticaFlow
{
    const STEP_SELEZIONA_ENTE = 1;
    const STEP_ACCETTAZIONE_ISTRUZIONI = 2;
    const STEP_DATI_RICHIEDENTE = 3;
    const STEP_DELEGA = 4;
    const STEP_SPECIFICA_DELEGA = 5;
    const STEP_CERTIFICATO_ANAGRAFICO = 6;

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
            self::STEP_DELEGA => array(
                'label' => 'steps.common.delega.label',
                'form_type' => DelegaType::class,
            ),
            self::STEP_SPECIFICA_DELEGA => array(
                'label' => 'steps.common.specifica_delega.label',
                'form_type' => SpecificaDelegaType::class,
                'skip' => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
                    $data = is_array($flow->getFormData()->getDelegaData()) ? $flow->getFormData()->getDelegaData() : json_decode($flow->getFormData()->getDelegaData(), true);
                    return !isset($data['has_delega']) ? false : !$data['has_delega'];
                }
            ),
            self::STEP_CERTIFICATO_ANAGRAFICO => array(
                'label' => 'steps.common.certificato_anagrafico.label',
                'form_type' => CertificatoAnagraficoType::class,
            ),
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
