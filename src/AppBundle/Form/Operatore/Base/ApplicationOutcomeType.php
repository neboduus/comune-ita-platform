<?php

namespace AppBundle\Form\Operatore\Base;

use AppBundle\Dto\ApplicationOutcome;
use AppBundle\Entity\Pratica;
use AppBundle\Form\Admin\Servizio\PaymentDataType;
use AppBundle\Services\FormServerApiAdapterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class ApplicationOutcomeType extends AbstractType
{
  /**
   * @var EntityManagerInterface
   */
  private $entityManager;


  /**
   * ApplicationOutcomeType constructor.
   * @param EntityManagerInterface $entityManager
   */
  public function __construct(EntityManagerInterface $entityManager)
  {
    $this->entityManager = $entityManager;
  }

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    /** @var ApplicationOutcome $outcome */
    $outcome = $builder->getData();

    $repo = $this->entityManager->getRepository('AppBundle:Pratica');
    /** @var Pratica $application */
    $application = $repo->find($outcome->getApplicationId());

    $helper = $options["helper"];
    $helper->setGuideText('operatori.flow.approva_o_rigetta.guida_alla_compilazione', true);
    $helper->setVueApp('outcome_attachments');
    $helper->setVueBundledData(json_encode([
      'applicationId' => $outcome->getApplicationId(),
      'attachments' => [],
      'prefix' => $helper->getPrefix(),
    ]));

    $builder->add(
      "outcome",
      ChoiceType::class,
      [
        "label" => 'operatori.flow.approva_o_rigetta.motivazione.esito_label',
        "required" => true,
        "expanded" => true,
        "multiple" => false,
        "choices" => [
          "Approva" => true,
          "Rigetta" => false,
        ],
      ]
    )->add(
      "message",
      TextareaType::class,
      [
        "label" => 'operatori.flow.approva_o_rigetta.motivazione.esito_label',
        "required" => false,
      ]
    )->add(
      "attachments",
      HiddenType::class,
      [
        "label" => 'operatori.flow.approva_o_rigetta.motivazione.allega_label',
        "required" => false,
      ]
    );

    if ($application->getServizio()->isPaymentDeferred()) {
      $builder->add(PaymentDataType::PAYMENT_AMOUNT, MoneyType::class, [
        'required' => false,
        'data' => $application->getPaymentAmount(),
        'label' => 'Importo da pagare'
      ]);
    }
  }

  public function getBlockPrefix()
  {
    return 'outcome';
  }
}
