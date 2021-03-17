<?php

namespace AppBundle\Form\FormIO;

use AppBundle\Entity\PaymentGateway;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Form\Base\PaymentGatewayType;
use AppBundle\Form\Base\PraticaFlow;
use AppBundle\Form\Base\RecaptchaType;
use AppBundle\Form\Base\SelectPaymentGatewayType;
use AppBundle\Form\Base\SelezionaEnteType;
use AppBundle\Form\Base\SummaryType;
use Craue\FormFlowBundle\Form\FormFlowInterface;

class FormIOFlow extends PraticaFlow
{


  const STEP_SELEZIONA_ENTE = 1;

  const STEP_MODULO_FORMIO = 2;

  protected $allowDynamicStepNavigation = true;

  protected $revalidatePreviousSteps = false;

  public function onFlowCompleted(Pratica $pratica)
  {
    if ($pratica->getType() == Pratica::TYPE_FORMIO) {
      $schema = $this->formIOFactory->createFromFormId($pratica->getServizio()->getFormIoId());
      if (!empty($pratica->getDematerializedForms()['data'])) {
        $data = $schema->getDataBuilder()->setDataFromArray($pratica->getDematerializedForms()['data'])->toFullFilledFlatArray();
        if (isset($data['related_applications'])) {
          $parentId = trim($data['related_applications']);
          $parent = $this->em->getRepository('AppBundle:Pratica')->find($parentId);
          if ($parent instanceof Pratica) {
            $pratica->setParent($parent);
            $pratica->setServiceGroup($parent->getServizio()->getServiceGroup());
            $pratica->setFolderId($parent->getFolderId());
          }
        }
      }
    }
    parent::onFlowCompleted($pratica);
  }

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
      ),
    );

    // Mostro lo step del'ente solo se è necesario
    if ($pratica->getEnte() == null && $this->prefix == null) {
      $steps [self::STEP_SELEZIONA_ENTE] = array(
        'label' => 'steps.common.seleziona_ente.label',
        'form_type' => SelezionaEnteType::class,
      );
    }
    ksort($steps);

    // Attivo gli step di pagamento solo se è richiesto nel servizio
    if ($pratica->getServizio()->isPaymentRequired() || ($pratica->getServizio()->isPaymentDeferred() && $pratica->getEsito() )) {
      $steps[] = array(
        'label' => 'steps.common.conferma.label',
        'form_type' => SummaryType::class,
        'form_options' => [
          'validation_groups' => 'recaptcha',
        ],
        'skip' => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
          return $flow->getFormData()->getStatus() == Pratica::STATUS_PAYMENT_PENDING && $flow->getFormData()->getServizio()->isPaymentDeferred() && $flow->getFormData()->getEsito();
        },
      );
      $steps[] = array(
        'label' => 'steps.common.select_payment_gateway.label',
        'form_type' => SelectPaymentGatewayType::class,
        'skip' => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
          return $flow->getFormData()->getStatus() == Pratica::STATUS_PAYMENT_PENDING && $flow->getFormData()->getPaymentType();
        },
      );
      $steps[] = array(
        'label' => 'steps.common.payment_gateway.label',
        'form_type' => PaymentGatewayType::class,
      );
      $steps[] = array(
        'label' => 'Verifica pagamento',
      );
    } else {
      // Step conferma
      if ($pratica->getUser() != null) {
        $steps[] = array(
          'label' => 'steps.common.conferma.label',
        );
      } else {
        $steps[] = array(
          'label' => 'steps.common.conferma.label',
          'form_type' => RecaptchaType::class,
          'form_options' => [
            'validation_groups' => 'recaptcha',
          ],
        );
      }

    }

    return $steps;
  }
}
