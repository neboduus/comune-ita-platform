<?php

namespace AppBundle\Form\Admin;

use AppBundle\Entity\Servizio;
use AppBundle\Form\Admin\Servizio\FeedbackMessagesDataType;
use AppBundle\Form\Admin\Servizio\FormIOBuilderRenderType;
use AppBundle\Form\Admin\Servizio\FormIOTemplateType;
use AppBundle\Form\Admin\Servizio\GeneralDataType;
use AppBundle\Form\Admin\Servizio\IntegrationsDataType;
use AppBundle\Form\Admin\Servizio\IOIntegrationDataType;
use AppBundle\Form\Admin\Servizio\PaymentDataType;
use AppBundle\Form\Admin\Servizio\ProtocolDataType;
use AppBundle\Logging\LogConstants;
use Craue\FormFlowBundle\Form\FormFlow;
use Craue\FormFlowBundle\Form\FormFlowInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;


class ServiceFlow extends FormFlow
{

  const STEP_FORM_TEMPLATE = 1;
  const STEP_GENERAL_DATA = 2;
  const STEP_FORM_FIELDS = 3;
  const STEP_FEEDBACK_MESSAGES_DATA = 4;
  const STEP_IO = 5;
  const STEP_PAYMENT_DATA = 6;
  const STEP_INTEGRATIONS_DATA = 7;
  const STEP_PROTOCOL_DATA = 8;

  /**
   * @var LoggerInterface
   */
  private $logger;

  /**
   * @var TranslatorInterface
   */
  private $translator;

  protected $allowDynamicStepNavigation = true;

  /**
   * PraticaOperatoreFlow constructor.
   *
   * @param LoggerInterface $logger
   * @param TranslatorInterface $translator
   */
  public function __construct(LoggerInterface $logger, TranslatorInterface $translator)
  {
    $this->logger = $logger;
    $this->translator = $translator;
  }

  protected function loadStepsConfig()
  {
    $steps[self::STEP_IO] = array(
      'label' => $this->translator->trans('app_io.title'),
      'form_type' => IOIntegrationDataType::class,
      'skip' => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
        /** @var Servizio $service */
        $service = $flow->getFormData();
        return !$service->getEnte()->isIOEnabled();
      }
    );

    // Mostro lo step per la configurazione di formio solo se necessario
    if ($this->getFormData()->getPraticaFCQN() == '\AppBundle\Entity\FormIO') {
      $steps[self::STEP_FORM_TEMPLATE] = array(
        'label' => $this->translator->trans('general.form_template'),
        'form_type' => FormIOTemplateType::class,
        'skip' => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
          /** @var Servizio $service */
          $service = $flow->getFormData();
          return !empty($service->getFormIoId());
        }
      );
    }

    $steps[self::STEP_GENERAL_DATA] = array(
      'label' => $this->translator->trans('operatori.dati_generali'),
      'form_type' => GeneralDataType::class
    );

    // Mostro lo step per la configurazione di formio solo se necessario
    if ($this->getFormData()->getPraticaFCQN() == '\AppBundle\Entity\FormIO') {

      $steps[self::STEP_FORM_FIELDS] = array(
        'label' => $this->translator->trans('operatori.form_field'),
        'form_type' => FormIOBuilderRenderType::class,
      );
    }

    $steps[self::STEP_FEEDBACK_MESSAGES_DATA] = array(
      'label' => $this->translator->trans('messages.messages_label'),
      'form_type' => FeedbackMessagesDataType::class
    );

    $steps[self::STEP_PAYMENT_DATA] = array(
      'label' => $this->translator->trans('general.payment_data'),
      'form_type' => PaymentDataType::class,
      'skip' => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
        /** @var Servizio $service */
        $service = $flow->getFormData();
        return empty($service->getEnte()->getGateways());
      }
    );

    $steps[self::STEP_INTEGRATIONS_DATA] = array(
      'label' => $this->translator->trans('integrations'),
      'form_type' => IntegrationsDataType::class,
      'skip' => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
        /** @var Servizio $service */
        $service = $flow->getFormData();
        return empty($service->getEnte()->getBackofficeEnabledIntegrations());
      }
    );

    $steps[self::STEP_PROTOCOL_DATA] = array(
      'label' => $this->translator->trans('general.protocol_data'),
      'form_type' => ProtocolDataType::class
    );

    ksort($steps);

    return $steps;
  }

  public function getFormOptions($step, array $options = array())
  {
    $options = parent::getFormOptions($step, $options);

    /** @var Servizio $servizio */
    $servizio = $this->getFormData();

    $this->logger->info(
      LogConstants::PRATICA_COMPILING_STEP,
      [
        'step' => $step,
        'servizio' => $servizio->getId()
      ]
    );

    return $options;
  }
}
