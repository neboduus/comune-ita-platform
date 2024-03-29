<?php

namespace App\Form\Operatore\Standard;

use Craue\FormFlowBundle\Form\FormFlowInterface;
use App\Form\Operatore\Base\ApprovaORigettaType;
use App\Form\Operatore\Base\PraticaOperatoreFlow;
use App\Form\Operatore\Base\UploadAllegatoOperatoreType;
use App\Form\Operatore\Base\UploadRispostaOperatoreType;

/**
 * Class StandardAllegatoNoFirmaFlow
 */
class StandardAllegatoNoFirmaFlow extends PraticaOperatoreFlow
{
    const STEP_APPROVA_O_RIGETTA = 1;
    const STEP_ALLEGA = 2;
    const STEP_ALLEGA_RISPOSTA_FIRMATA = 3;

    protected $allowDynamicStepNavigation = true;

    protected function loadStepsConfig()
    {
        return array(
            self::STEP_APPROVA_O_RIGETTA => array(
                'label' => 'operatori.approva',
                'form_type' => ApprovaORigettaType::class
            ),
            self::STEP_ALLEGA => array(
                'label' => 'operatori.allega',
                'form_type' => UploadAllegatoOperatoreType::class,
                'skip' => function($estimatedCurrentStepNumber, FormFlowInterface $flow) {
                    return $flow->getFormData()->getEsito() === false;
                }
            )
        );
    }

}
