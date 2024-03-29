<?php

namespace App\Form\Admin\Servizio;

use App\Model\DefaultProtocolSettings;

use App\Model\FeedbackMessagesSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FeedbackMessagesSettingsType extends AbstractType
{
  /**
   * {@inheritdoc}
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    $builder
      ->add('pec_mailer', ChoiceType::class, [
        'label' => 'Servizio di invio messaggi',
        'choices' => $options['mailers'],
      ])
      ->add('pec_receiver', ChoiceType::class, [
        'required' => false,
        'label' => "Specificare il campo del form dove è inserita l'email del destinatario",
        'choices' => $options['components'],
      ]);
  }

  /**
   * {@inheritdoc}
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => FeedbackMessagesSettings::class,
      'csrf_protection' => false,
      'mailers' => null,
      'components' => null
    ));

    $resolver->setAllowedTypes('mailers', ['null', 'array']);
    $resolver->setAllowedTypes('components', ['null', 'array']);
  }
}
