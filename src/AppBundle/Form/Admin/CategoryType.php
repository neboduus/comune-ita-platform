<?php


namespace AppBundle\Form\Admin;

use AppBundle\Entity\Categoria;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryType extends AbstractType
{


  /**
   * {@inheritdoc}
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    $builder
      ->add('name', TextType::class, [
        'label' => 'general.nome',
        'required' => true
      ])
      ->add('description', TextareaType::class, [
        'label' => 'general.descrizione',
        'required' => false
      ])
      ->add(
        'parent_id',
        EntityType::class,
        [
          'class' => 'AppBundle\Entity\Categoria',
          'property_path' => 'parent',
          'choice_label' => 'name',
          'required' => false,
          'label' => 'categories.parent',
        ]
      )
    ;
  }

  /**
   * {@inheritdoc}
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => Categoria::class,
      'csrf_protection' => false
    ));
  }
}
