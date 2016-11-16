<?php

namespace AppBundle\Form\Operatore\AttestazioneAnagrafica;

use AppBundle\Form\Operatore\Base\PraticaOperatoreFlow;
use AppBundle\Form\Operatore\AttestazioneAnagrafica\UploadAttestazioneAnagraficaType;

/**
 * Class AttestazioneAnagraficaOperatoreFlow
 */
class AttestazioneAnagraficaOperatoreFlow extends PraticaOperatoreFlow
{
    const STEP_ALLEGA = 1;
    const STEP_APPROVA = 2;

    protected $allowDynamicStepNavigation = true;

    protected function loadStepsConfig()
    {
        return array(
            self::STEP_ALLEGA => array(
                'label' => 'operatori.allega',
                'form_type' => UploadAttestazioneAnagraficaType::class,
            ),
            self::STEP_APPROVA => array(
                'label' => 'operatori.approva'
            )
        );
    }
}
