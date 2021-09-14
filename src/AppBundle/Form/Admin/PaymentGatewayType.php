<?php


namespace AppBundle\Form\Admin;

use AppBundle\Entity\PaymentGateway;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentGatewayType extends AbstractType
{


  /**
   * {@inheritdoc}
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    $builder
      ->add('name', TextType::class, [
        'label' => 'gateway.name',
        'required' => true
      ])
      ->add('identifier', TextType::class, [
        'label' => 'Identifier',
        'required' => true
      ])
      ->add('url', UrlType::class, [
        'label' => 'gateway.url',
        'required' => true
      ])
      ->add('description', TextareaType::class, [
        'label' => 'Description',
        'required' => false
      ])
      ->add('disclaimer', TextareaType::class, [
        'label' => 'Disclaimer',
        'required' => false
      ])
      ->add('enabled', CheckboxType::class, [
        'label' =>  'Attivo?' ,
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
      'data_class' => PaymentGateway::class
    ));
  }
}
