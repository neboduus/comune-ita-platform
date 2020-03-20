<?php

namespace AppBundle\Form\FormIO;

use AppBundle\Entity\Pratica;
use AppBundle\Form\Base\AccettazioneIstruzioniType;
use AppBundle\Form\Base\DatiRichiedenteType;
use AppBundle\Form\Base\PraticaFlow;
use AppBundle\Form\Base\RecaptchaType;
use AppBundle\Form\Base\SelezionaEnteType;
use AppBundle\Form\Base\SummaryType;
use AppBundle\Form\Scia\PraticaEdiliziaVincoliType;
use Craue\FormFlowBundle\Form\FormFlow;
use Craue\FormFlowBundle\Form\FormFlowInterface;
use AppBundle\Form\Base\SelectPaymentGatewayType;
use AppBundle\Form\Base\PaymentGatewayType;

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
      'form_type' => RecaptchaType::class
    );

    return $steps;
  }
}
