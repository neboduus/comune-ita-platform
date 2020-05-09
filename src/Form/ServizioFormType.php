<?php

namespace App\Form;

use App\Entity\Categoria;
use App\Entity\Servizio;
use App\Services\FormServerApiAdapterService;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServizioFormType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
      ->add('name')
      ->add('tenant', EntityType::class, [
        'class' => 'App\Entity\Ente',
        'choice_label' => 'name',
      ])
      /*->add('topics', EntityType::class, [
        'class' => 'App\Entity\Categoria',
        'choice_label' => 'name',
      ])*/
      ->add('topics')
      ->add('description')
      ->add('howto')
      ->add('who')
      ->add('special_cases')
      ->add('more_info')
      ->add('coverage', CollectionType::class, [
        'entry_type' => TextType::class,
        "allow_add" => true,
        "allow_delete" => true,
        'prototype' => true,
        "label" => false
      ])
      ->add('response_type')
      ->add('flow_steps', CollectionType::class, [
        'entry_type' => FlowStepType::class,
        "allow_add" => true,
        "allow_delete" => true,
        'prototype' => true,
        "label" => false
      ])
      ->add('protocollo_parameters')
      ->add('payment_required', CheckboxType::class)
      ->add('payment_parameters', PaymentParametersType::class, [
        'data_class' => null
      ])
      ->add('sticky')
      ->add('status');
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
      'data_class' => 'App\Dto\Service',
      'csrf_protection' => false
    ));
    }
}
