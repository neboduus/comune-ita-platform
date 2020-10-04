<?php

namespace App\Form\Operatore\Standard;

use App\Form\Operatore\Base\ApprovaORigettaType;
use App\Form\Operatore\Base\PraticaOperatoreFlow;
use App\Form\Operatore\Base\UploadRispostaOperatoreType;

/**
 * Class StandardOperatoreFlow
 */
class StandardOperatoreFlow extends PraticaOperatoreFlow
{
    const STEP_APPROVA_O_RIGETTA = 1;
    protected $allowDynamicStepNavigation = true;

    protected function loadStepsConfig()
    {
        return array(
            self::STEP_APPROVA_O_RIGETTA => array(
                'label' => 'operatori.approva',
                'form_type' => ApprovaORigettaType::class
            )
        );
    }
}
