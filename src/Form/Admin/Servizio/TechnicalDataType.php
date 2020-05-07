<?php


namespace App\Form\Admin\Servizio;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class TechnicalDataType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            "name",
            TextType::class,
            [
                "label"    => 'Nome del servizio',
                "required" => true,
            ]
        )
        ->add('topics', EntityType::class, [
            'class' => 'App\Entity\Categoria',
            'choice_label' => 'name',
        ])
        ->add('description')
        ->add('howto')
        ->add('who')
        ->add('special_cases')
        ->add('more_info')
        ->add('coverage', CollectionType::class, [
            'entry_type' => TextType::class,
            "entry_options" => ["label" => 'aaaa'],
            'allow_add' => true,
            'prototype' => true
        ]);
    }

    public function getBlockPrefix()
    {
        return 'technical_data';
    }
}
