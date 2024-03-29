<?php

namespace App\Form\FormIO;

use App\Entity\Pratica;
use App\Entity\Servizio;
use App\Form\Admin\Servizio\PaymentDataType;
use App\Form\Base\PaymentGatewayType;
use App\Form\Base\PraticaFlow;
use App\Form\Base\RecaptchaType;
use App\Form\Base\SelectPaymentGatewayType;
use App\Form\Base\SelezionaEnteType;
use App\Form\Base\SummaryType;
use Craue\FormFlowBundle\Form\FormFlowInterface;

class FormIOFlow extends PraticaFlow
{

  const STEP_MODULO_FORMIO = 1;

  protected $allowDynamicStepNavigation = true;

  protected $revalidatePreviousSteps = false;

  public function onFlowCompleted(Pratica $pratica)
  {
    if ($pratica->isFormIOType()) {
      $schema = $this->formIOFactory->createFromFormId($pratica->getServizio()->getFormIoId());
      if (!empty($pratica->getDematerializedForms()['data'])) {
        $data = $schema->getDataBuilder()->setDataFromArray($pratica->getDematerializedForms()['data'])->toFullFilledFlatArray();
        if (isset($data['related_applications'])) {
          $parentId = trim($data['related_applications']);
          $parent = $this->em->getRepository('App\Entity\Pratica')->find($parentId);
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
    $data = $pratica->getDematerializedForms();

    $steps = array(
      self::STEP_MODULO_FORMIO => array(
        'label' => '',
        'form_type' => FormIORenderType::class,
        'skip' => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
          return $flow->getFormData()->getStatus() != Pratica::STATUS_DRAFT;
        },
      ),
    );

    // Attivo gli step di pagamento solo se è richiesto nel servizio
    if ($pratica->getServizio()->isPaymentRequired() || ($pratica->getServizio()->isPaymentDeferred() && $pratica->getEsito() )) {
      $steps[] = array(
        'label' => 'steps.common.conferma.label',
        'form_type' => SummaryType::class,
        'form_options' => [
          'validation_groups' => 'recaptcha',
        ],
        'skip' => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
          return ($flow->getFormData()->getStatus() == Pratica::STATUS_PAYMENT_PENDING && $flow->getFormData()->getServizio()->isPaymentDeferred() && $flow->getFormData()->getEsito())
                 or ($flow->getFormData()->getStatus() == Pratica::STATUS_PAYMENT_PENDING && $flow->getFormData()->getPaymentType() && !empty($flow->getFormData()->getPaymentData()));
        },
      );

      if (empty($data) || (isset($data['flattened'][PaymentDataType::PAYMENT_AMOUNT]) && $data['flattened'][PaymentDataType::PAYMENT_AMOUNT] > 0)) {
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
          'label' => $this->translator->trans('payment.check_payment'),
        );
      }
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
