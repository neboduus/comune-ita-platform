<?php

namespace App\Form\FormIO;

use App\Entity\Pratica;
use App\Form\Base\AccettazioneIstruzioniType;
use App\Form\Base\DatiRichiedenteType;
use App\Form\Base\PraticaFlow;
use App\Form\Base\RecaptchaType;
use App\Form\Base\SelezionaEnteType;
use App\Form\Base\SummaryType;
use App\Form\Scia\PraticaEdiliziaVincoliType;
use Craue\FormFlowBundle\Form\FormFlow;
use Craue\FormFlowBundle\Form\FormFlowInterface;
use App\Form\Base\SelectPaymentGatewayType;
use App\Form\Base\PaymentGatewayType;

class FormIOAnonymousFlow extends PraticaFlow
{
    const STEP_MODULO_FORMIO = 1;


    protected $allowDynamicStepNavigation = true;

    protected function loadStepsConfig()
    {
        $steps = [];
        $steps[] = array(
      'label' => 'steps.scia.modulo_default.label',
      'form_type' => FormIOAnonymousRenderType::class,
      'skip' => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
          return $flow->getFormData()->getStatus() != Pratica::STATUS_DRAFT;
      },
    );

        // Step conferma
        $steps[] = array(
      'label'     => 'steps.common.conferma.label',
      'form_type' => RecaptchaType::class,
      'form_options' => [
        'validation_groups' => 'recaptcha'
      ]
    );

        return $steps;
    }
}
