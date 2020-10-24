<?php

namespace App\Form\Rest;

use App\Model\File;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FileType extends AbstractType
{
  /**
   * {@inheritdoc}
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('name', TextType::class, [
        'required' => true
      ])
      ->add('mime_type', TextType::class, [
        'required' => true
      ])
      ->add('file', TextType::class, [
        'required' => true
      ]);
    }

  /**
   * {@inheritdoc}
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => File::class,
      'allow_extra_fields' => true,
      'csrf_protection' => false
    ));
  }
}
