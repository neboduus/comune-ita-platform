<?php

namespace AppBundle\Form\FormIO;

use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Form\Base\AccettazioneIstruzioniType;
use AppBundle\Form\Base\DatiRichiedenteType;
use AppBundle\Form\Base\PraticaFlow;
use AppBundle\Form\Base\RecaptchaType;
use AppBundle\Form\Base\SelezionaEnteType;
use AppBundle\Form\Base\SummaryType;
use AppBundle\Form\Scia\PraticaEdiliziaVincoliType;
use Craue\FormFlowBundle\Form\FormFlowInterface;
use AppBundle\Form\Base\SelectPaymentGatewayType;
use AppBundle\Form\Base\PaymentGatewayType;

class FormIOFlow extends PraticaFlow
{
  const STEP_DATI_RICHIEDENTE = 1;
  const STEP_MODULO_FORMIO = 2;


  protected $allowDynamicStepNavigation = true;

  protected function loadStepsConfig()
  {
    /** @var Pratica $pratica */
    $pratica = $this->getFormData();

    $steps = array(
      self::STEP_MODULO_FORMIO => array(
        'label' => 'steps.scia.modulo_default.label',
        'form_type' => FormIORenderType::class,
        'skip' => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
          return $flow->getFormData()->getStatus() != Pratica::STATUS_DRAFT;
        },
      )
    );

    // Mostro lo step del'ente solo se è necesario
    if ($pratica->getEnte() == null && $this->prefix == null) {
      $steps [self::STEP_SELEZIONA_ENTE] = array(
        'label' => 'steps.common.seleziona_ente.label',
        'form_type' => SelezionaEnteType::class
      );
    }
    ksort($steps);

    // Attivo gli step di pagamento solo se è richiesto nel servizio
    if ($this->isPaymentRequired()) {
      $steps[] = array(
        'label' => 'steps.common.conferma.label',
        'form_type' => SummaryType::class,
        'skip' => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
          return $flow->getFormData()->getStatus() == Pratica::STATUS_PAYMENT_PENDING;
        },
      );
      $steps[] = array(
        'label' => 'steps.common.select_payment_gateway.label',
        'form_type' => SelectPaymentGatewayType::class,
        'skip' => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
          return $flow->getFormData()->getStatus() == Pratica::STATUS_PAYMENT_PENDING;
        },
      );
      $steps[] = array(
        'label' => 'steps.common.payment_gateway.label',
        'form_type' => PaymentGatewayType::class
      );
      $steps[] = array(
        'label' => 'Verifica pagamento'
      );
    } else {
      // Step conferma
      if ( $pratica->getServizio()->getAccessLevel() > Servizio::ACCESS_LEVEL_ANONYMOUS) {
        $steps[] = array(
          'label' => 'steps.common.conferma.label'
        );
      } else {
        $steps[] = array(
          'label'     => 'steps.common.conferma.label',
          'form_type' => RecaptchaType::class,
          'form_options' => [
            'validation_groups' => 'recaptcha'
          ]
        );
      }

    }

    return $steps;
  }
}
