<?php
namespace AppBundle\Form\Scia;

use AppBundle\Entity\DematerializedFormAllegatiContainer;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\SciaPraticaEdilizia;
use AppBundle\Form\Base\AccettazioneIstruzioniType;
use AppBundle\Form\Base\DatiTecnicoType;
use AppBundle\Form\Base\PraticaFlow;
use AppBundle\Form\Base\SelezionaEnteType;
use Craue\FormFlowBundle\Form\FormFlowInterface;

class SciaPraticaEdiliziaFlow extends PraticaFlow
{
    const STEP_SELEZIONA_ENTE = 1;
    const STEP_ACCETTAZIONE_ISTRUZIONI = 2;
    const STEP_DATI_TECNICO = 3;
    const STEP_MODULO_SCIA = 4;
    const STEP_ALLEGATI_MODULO_SCIA = 5;
    const STEP_SOGGETTI = 6;
    const STEP_ALLEGATI_TECNICI = 7;
    const STEP_ULTERIORI_ALLEGATI_TECNICI = 8;
    const STEP_PROVVEDIMENTI = 9;
    const STEP_CONFERMA = 10;

    protected $allowDynamicStepNavigation = true;

    protected function loadStepsConfig()
    {
        return array(
            self::STEP_SELEZIONA_ENTE => array(
                'label' => 'steps.common.seleziona_ente.label',
                'form_type' => SelezionaEnteType::class,
                'skip' => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
                    return $flow->getFormData()->haUnaRichiestaDiIntegrazioneAttiva();
                },
            ),
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
                'label' => 'steps.scia.modulo_scia.label',
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
            self::STEP_SOGGETTI => array(
                'label' => 'steps.scia.soggetti.label',
                'form_type' => PraticaEdiliziaSoggettiType::class,
                'skip' => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
                    return $flow->getFormData()->haUnaRichiestaDiIntegrazioneAttiva() && !PraticaEdiliziaSoggettiType::getRequestIntegrations($flow->getFormData());
                },
            ),
            self::STEP_ALLEGATI_TECNICI => array(
                'label' => 'steps.scia.allegati_tecnici.label',
                'form_type' => PraticaEdiliziaAllegatiTecniciType::class,
                'skip' => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
                    return $flow->getFormData()->haUnaRichiestaDiIntegrazioneAttiva() && !PraticaEdiliziaAllegatiTecniciType::getRequestIntegrations($flow->getFormData());
                },
            ),
            self::STEP_ULTERIORI_ALLEGATI_TECNICI => array(
                'label' => 'steps.scia.ulteriori_allegati_tecnici.label',
                'form_type' => PraticaEdiliziaUlterioriAllegatiTecniciType::class,
                'skip' => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
                    return $flow->getFormData()->haUnaRichiestaDiIntegrazioneAttiva() && !PraticaEdiliziaUlterioriAllegatiTecniciType::getRequestIntegrations($flow->getFormData());
                },
            ),
            self::STEP_PROVVEDIMENTI => array(
                'label' => 'steps.scia.provvedimenti.label',
                'form_type' => PraticaEdiliziaProvvedimentiType::class,
                'skip' => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
                    return $flow->getFormData()->haUnaRichiestaDiIntegrazioneAttiva() && !PraticaEdiliziaProvvedimentiType::getRequestIntegrations($flow->getFormData());
                },
            ),
            self::STEP_CONFERMA => array(
                'label' => 'steps.common.conferma.label',
            ),
        );
    }

}