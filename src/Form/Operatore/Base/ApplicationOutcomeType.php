<?php

namespace App\Form\Operatore\Base;

use App\Dto\ApplicationOutcome;
use App\Entity\Pratica;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class ApplicationOutcomeType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    /** @var ApplicationOutcome $outcome */
    $outcome = $builder->getData();

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
  }

  public function getBlockPrefix()
  {
    return 'outcome';
  }
}
