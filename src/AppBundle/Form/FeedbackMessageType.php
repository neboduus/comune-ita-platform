<?php

namespace AppBundle\Form;

use AppBundle\Model\FeedbackMessage;
use SebastianBergmann\CodeCoverage\Report\Text;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FeedbackMessageType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    $builder
      ->add('name', TextType::class, [
        'label' => 'Stato della richiesta',
        'attr' => ['readonly' => 'readonly'],
        'required' => false
      ])
      ->add('is_active', CheckboxType::class, [
        'label' => "Abilitare l'invio del messaggio?",
        'required' => false
      ])
      ->add('trigger', HiddenType::class)
      ->add('message', TextareaType::class, [
        'label' => "Messaggio (placeholder disponibili: %pratica_id%, %servizio%, %protocollo%, %motivazione%, %user_name%)"
      ]);
  }

  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => FeedbackMessage::class,
      'csrf_protection' => false,
    ));
  }

  public function getBlockPrefix()
  {
    return 'app_bundle_feedback_message_type';
  }
}
