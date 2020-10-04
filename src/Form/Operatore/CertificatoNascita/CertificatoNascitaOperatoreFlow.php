<?php

namespace App\Form\Operatore\CertificatoNascita;

use App\Form\Operatore\Base\ApprovaORigettaType;
use App\Form\Operatore\Base\PraticaOperatoreFlow;
use App\Form\Operatore\Base\UploadRispostaOperatoreType;
use Craue\FormFlowBundle\Form\FormFlowInterface;

/**
 * Class CertificatoNascitaOperatoreFlow
 */
class CertificatoNascitaOperatoreFlow extends PraticaOperatoreFlow
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
                'form_type' => ApprovaORigettaType::class,
            ),
            self::STEP_ALLEGA => array(
                'label' => 'operatori.allega',
                'form_type' => UploadCertificatoNascitaType::class,
                'skip' => function($estimatedCurrentStepNumber, FormFlowInterface $flow) {
                    return $flow->getFormData()->getEsito() === false;
                }
            ),
            self::STEP_ALLEGA_RISPOSTA_FIRMATA => array(
                'label' => 'operatori.allega_risposta_firmata',
                'form_type' => UploadRispostaOperatoreType::class,
            )
        );
    }
}
