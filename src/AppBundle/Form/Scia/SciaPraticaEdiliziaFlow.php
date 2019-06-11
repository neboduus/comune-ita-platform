<?php
namespace AppBundle\Form\Scia;

use AppBundle\Entity\ComunicazioneOpereLibere;
use AppBundle\Entity\Pratica;
use AppBundle\Form\Base\AccettazioneIstruzioniType;
use AppBundle\Form\Base\DatiTecnicoType;
use AppBundle\Form\Base\PraticaFlow;
use AppBundle\Form\Base\SelezionaEnteType;
use Craue\FormFlowBundle\Form\FormFlowInterface;
use AppBundle\Form\Base\SelectPaymentGatewayType;
use AppBundle\Form\Base\PaymentGatewayType;

class SciaPraticaEdiliziaFlow extends PraticaFlow
{
    const STEP_SELEZIONA_ENTE = 1;
    const STEP_ACCETTAZIONE_ISTRUZIONI = 2;
    const STEP_DATI_TECNICO = 3;
    const STEP_MODULO_SCIA = 4;
    const STEP_ALLEGATI_MODULO_SCIA = 5;
    const STEP_SOGGETTI = 6;
    const STEP_ALLEGATI_TECNICI = 7;
    const STEP_VINCOLI = 8;
    const STEP_PROVVEDIMENTI = 9;
    const STEP_CONFERMA = 10;

    protected $allowDynamicStepNavigation = true;

    protected function loadStepsConfig()
    {

        $pratica = $this->getFormData();
        $tipo = $pratica->getDematerializedForms()['tipo'];

        $steps =  array(
            self::STEP_ACCETTAZIONE_ISTRUZIONI => array(
                'label' => 'steps.common.accettazione_istruzioni.label',
                'form_type' => AccettazioneIstruzioniType::class,
                'skip' => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
                    return $flow->getFormData()->haUnaRichiestaDiIntegrazioneAttiva();
                },
            ),
            self::STEP_DATI_TECNICO => array(
                'label' => 'steps.common.dati_richiedente.label',
                'form_type' => DatiTecnicoType::class,
                'skip' => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
                    return $flow->getFormData()->haUnaRichiestaDiIntegrazioneAttiva();
                },
            ),
            self::STEP_MODULO_SCIA => array(
                'label' => 'steps.scia.modulo_default.label',
                'form_type' => PraticaEdiliziaModuloSciaType::class,
                'skip' => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
                    return $flow->getFormData()->haUnaRichiestaDiIntegrazioneAttiva() && !PraticaEdiliziaModuloSciaType::getRequestIntegrations($flow->getFormData());
                },
            ),
            self::STEP_ALLEGATI_MODULO_SCIA => array(
                'label' => 'steps.scia.allegati_modulo_scia.label',
                'form_type' => PraticaEdiliziaAllegatiModuloSciaType::class,
                'skip' => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
                    return $flow->getFormData()->haUnaRichiestaDiIntegrazioneAttiva() && !PraticaEdiliziaAllegatiModuloSciaType::getRequestIntegrations($flow->getFormData());
                },
            ),
            self::STEP_ALLEGATI_TECNICI => array(
                'label' => 'steps.scia.allegati_tecnici.label',
                'form_type' => PraticaEdiliziaAllegatiTecniciType::class,
                'skip' => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
                    return $flow->getFormData()->haUnaRichiestaDiIntegrazioneAttiva() && !PraticaEdiliziaAllegatiTecniciType::getRequestIntegrations($flow->getFormData());
                },
            ),
            self::STEP_VINCOLI => array(
                'label' => 'steps.scia.vincoli.label',
                'form_type' => PraticaEdiliziaVincoliType::class,
                'skip' => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
                    return $flow->getFormData()->haUnaRichiestaDiIntegrazioneAttiva() && !PraticaEdiliziaVincoliType::getRequestIntegrations($flow->getFormData());
                },
            ),
        );




        switch($tipo) {
            case Pratica::TYPE_COMUNICAZIONE_OPERE_LIBERE:
            case Pratica::TYPE_DICHIARAZIONE_ULTIMAZIONE_LAVORI:
            case Pratica::TYPE_AUTORIZZAZIONE_PAESAGGISTICA_SINDACO:
                unset($steps[self::STEP_VINCOLI]);
                break;
            default:
                break;
        }


        // Mostro lo step del'ente solo se è necesario
        if ($pratica->getEnte() == null && $this->prefix == null)
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
