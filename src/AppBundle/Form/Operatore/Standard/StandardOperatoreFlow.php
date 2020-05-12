<?php

namespace AppBundle\Form\Operatore\Standard;

use AppBundle\Form\Operatore\Base\ApprovaORigettaType;
use AppBundle\Form\Operatore\Base\PraticaOperatoreFlow;
use AppBundle\Form\Operatore\Base\UploadRispostaOperatoreType;

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
