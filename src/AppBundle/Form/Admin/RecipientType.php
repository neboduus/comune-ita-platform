<?php


namespace AppBundle\Form\Admin;

use AppBundle\Entity\Recipient;
use AppBundle\Form\I18n\AbstractI18nType;
use AppBundle\Form\I18n\I18nTextareaType;
use AppBundle\Form\I18n\I18nTextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecipientType  extends AbstractI18nType
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
  }

  /**
   * {@inheritdoc}
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => Recipient::class,
      'csrf_protection' => false,
    ));
    $this->configureTranslationOptions($resolver);
  }
}
