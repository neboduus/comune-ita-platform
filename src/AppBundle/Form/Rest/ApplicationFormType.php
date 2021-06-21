<?php

namespace AppBundle\Form\Rest;


use AppBundle\Dto\Message;
use AppBundle\Entity\Servizio;
use AppBundle\Form\Rest\FileType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplicationFormType extends AbstractType
{

  /**
   * @param FormBuilderInterface $builder
   * @param array $options
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('user')
      ->add('service')
      ->add('data')
      ->add('protocol_folder_number')
      ->add('protocol_folder_code')
      ->add('protocol_number')
      ->add('protocol_document_id')
      ->add('protocolled_at')
      ->add('outcome')
      ->add('outcome_motivation')
      ->add('outcome_protocol_number')
      ->add('outcome_protocol_document_id')
      ->add('outcome_protocolled_at')
      ->add('payment_type')
      ->add('payment_data')
      ->add('status')
    ;
  }

  /**
   * @param OptionsResolver $resolver
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => 'AppBundle\Dto\Application',
      'allow_extra_fields' => true,
      'csrf_protection' => false
    ));
  }

}
