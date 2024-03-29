<?php

namespace App\Form\Admin\Ente;

use App\Model\DefaultProtocolSettings;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DefaultProtocolSettingsType extends AbstractType
{
  /**
   * {@inheritdoc}
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('certificate', TextareaType::class, [
        'label' => 'ente.certificato',
        'required' => false
      ])
      ->add('certificateKey', TextareaType::class, [
        'label' => 'ente.chiave_certificato',
        'required' => false
      ])
      ->add('certificatePassword', TextType::class, [
        'label' => 'ente.password_certifcato',
        'required' => false
      ])
    ;
  }

  /**
   * {@inheritdoc}
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => DefaultProtocolSettings::class,
      'csrf_protection' => false
    ));
  }
}
