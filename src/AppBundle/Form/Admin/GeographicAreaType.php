<?php


namespace AppBundle\Form\Admin;

use AppBundle\Entity\GeographicArea;
use AppBundle\Form\I18n\AbstractI18nType;
use AppBundle\Form\I18n\I18nTextareaType;
use AppBundle\Form\I18n\I18nTextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GeographicAreaType  extends AbstractI18nType
{


  /**
   * {@inheritdoc}
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $this->createTranslatableMapper($builder, $options)
      ->add("name", I18nTextType::class, [
        "label" => 'general.nome'
      ])
      ->add("description", I18nTextareaType::class, [
        "label" => 'general.descrizione'
      ]);

    $builder
      ->add('geofence', TextType::class, [
          'label' => false,
          'required' => false,
        ]
      );
  }

  /**
   * {@inheritdoc}
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => GeographicArea::class,
      'csrf_protection' => false,
    ));
    $this->configureTranslationOptions($resolver);
  }
}
