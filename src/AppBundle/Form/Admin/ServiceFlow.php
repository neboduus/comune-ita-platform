<?php

namespace AppBundle\Form\Admin;

use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Form\Admin\Servizio\FormIOBuilderRenderType;
use AppBundle\Form\Admin\Servizio\FormIOTemplateType;
use AppBundle\Form\Admin\Servizio\GeneralDataType;
use AppBundle\Form\Admin\Servizio\PaymentDataType;
use AppBundle\Form\Admin\Servizio\ProtocolDataType;
use AppBundle\Form\Base\SelezionaEnteType;
use AppBundle\Form\Base\SpecificaDelegaType;
use AppBundle\Form\Operatore\Base\ApprovaORigettaType;
use AppBundle\Form\Operatore\Base\UploadAllegatoOperatoreType;
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
  const STEP_PAYMENT_DATA = 4;
  const STEP_PROTOCOL_DATA = 5;

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
    // Mostro lo step per la configurazione di formio solo se necessario
    if ($this->getFormData()->getPraticaFCQN() == '\AppBundle\Entity\FormIO') {
      $steps[self::STEP_FORM_TEMPLATE] = array(
        'label' => 'Template del form',
        'form_type' => FormIOTemplateType::class,
        'skip' => function ($estimatedCurrentStepNumber, FormFlowInterface $flow) {
          $service = $flow->getFormData();
          $flowsteps = $service->getFlowSteps();
          $additionalData = $service->getAdditionalData();
          if (!empty($flowsteps)) {
            foreach ($flowsteps as $f) {
              if (isset($f['type']) && $f['type'] == 'formio' && isset($f['parameters']['formio_id']) && $f['parameters']['formio_id'] && !empty($f['parameters']['formio_id'])) {
                return true;
              }
            }
          }
          // Retrocompatibilità
          return isset($additionalData['formio_id']) ? true : false;
        }
      );
    }

    $steps[self::STEP_GENERAL_DATA] = array(
      'label' => 'Dati generali',
      'form_type' => GeneralDataType::class
    );

    // Mostro lo step per la configurazione di formio solo se necessario
    if ($this->getFormData()->getPraticaFCQN() == '\AppBundle\Entity\FormIO') {

      $steps[self::STEP_FORM_FIELDS] = array(
        'label' => 'Campi del form',
        'form_type' => FormIOBuilderRenderType::class,
      );
    }

    $steps[self::STEP_PAYMENT_DATA] = array(
      'label' => 'Dati pagamento',
      'form_type' => PaymentDataType::class
    );

    $steps[self::STEP_PROTOCOL_DATA] = array(
      'label' => 'Dati protocollo',
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
