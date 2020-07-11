<?php


namespace AppBundle\Form\Operatore\Base;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class ApprovaORigettaType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $helper = $options["helper"];
    $helper->setGuideText('operatori.flow.approva_o_rigetta.guida_alla_compilazione', true);

    $builder->add(
      "esito",
      ChoiceType::class,
      [
        "label" => 'operatori.flow.approva_o_rigetta.motivazione.esito_label',
        "required" => true,
        "expanded" => true,
        "multiple" => false,
        "choices" => [
          "Approva" => true,
          "Rigetta" => false
        ]
      ]
    );
    $builder->add(
      "motivazioneEsito",
      TextareaType::class,
      [
        "label" => 'operatori.flow.approva_o_rigetta.motivazione.esito_label',
        "required" => false,
        'helper' => 'My Help Message'
      ]
    );
  }

  public function getBlockPrefix()
  {
    return 'approva_o_rigetta';
  }
}
