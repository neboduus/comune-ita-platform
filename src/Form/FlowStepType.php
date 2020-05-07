<?php

namespace App\Form;

use App\Model\FlowStep;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FlowStepType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
      ->add('identifier')
      ->add('title')
      ->add('description')
      ->add('guide')
      ->add('type')
      ->add('parameters', TextareaType::class, []);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
      'data_class' => FlowStep::class,
      'csrf_protection' => false
    ));
    }
}
