<?php


namespace AppBundle\Form\Admin\Servizio;


use AppBundle\Entity\Servizio;
use AppBundle\Form\PaymentParametersType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class PaymentDataType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    $builder
      ->add('payment_required', CheckboxType::class)
      /*->add('payment_parameters', PaymentParametersType::class, [
        'label' => false,
        'data_class' => null
      ])*/;
  }

  public function getBlockPrefix()
  {
    return 'payment_data';
  }
}
