<?php


namespace App\Form\Admin\Servizio;


use App\Entity\Servizio;
use App\Form\I18n\AbstractI18nType;
use App\Form\I18n\I18nTextareaType;
use App\Form\I18n\I18nTextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CardDataType extends AbstractI18nType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    /** @var Servizio $servizio */
    $servizio = $builder->getData();


    // you can add the translatable fields
    $this->createTranslatableMapper($builder, $options)
      ->add("description", I18nTextareaType::class, [
        "label" => 'servizio.cos_e',
        'purify_html' => true,
      ])
      ->add("who", I18nTextareaType::class, [
        "label" => 'servizio.a_chi_si_rivolge',
        'purify_html' => true,
      ])
      ->add("howto", I18nTextareaType::class, [
        "label" => 'servizio.accedere',
        'purify_html' => true,
      ])
      ->add("howToDo", I18nTextareaType::class, [
        "label" => 'servizio.how_to_do',
        'purify_html' => true,
      ])
      ->add("whatYouNeed", I18nTextareaType::class, [
        "label" => 'servizio.what_you_need',
        'purify_html' => true,
      ])
      ->add("whatYouGet", I18nTextareaType::class, [
        "label" => 'servizio.what_you_get',
        'purify_html' => true,
      ])
      ->add("costs", I18nTextareaType::class, [
        "label" => 'servizio.costs',
        'purify_html' => true,
      ])
      ->add("specialCases", I18nTextareaType::class, [
        "label" => 'servizio.casi_particolari',
        'purify_html' => true,
      ])
      ->add("moreInfo", I18nTextareaType::class, [
        "label" => 'servizio.maggiori_info',
        'purify_html' => true,
      ])
    ;

    $builder
      ->add(
        'topics',
        EntityType::class,
        [
          'class' => 'App\Entity\Categoria',
          'choice_label' => 'name',
          'label' => 'servizio.categoria',
        ]
      )
      ->add(
        'recipients',
        EntityType::class,
        [
          'class' => 'App\Entity\Recipient',
          'choice_label' => 'name',
          'label' => 'servizio.destinatari',
          'attr' => ['style' => 'columns: 2;'],
          'required' => false,
          'multiple' => true,
          'expanded' => true
        ]
      )
      ->add(
        'geographicAreas',
        EntityType::class,
        [
          'class' => 'App\Entity\GeographicArea',
          'choice_label' => 'name',
          'label' => 'servizio.aree_geografiche',
          'attr' => ['style' => 'columns: 2;'],
          'required' => false,
          'multiple' => true,
          'expanded' => true
        ]
      )
      ->add(
        'coverage',
        TextType::class,
        [
          'label' => 'servizio.copertura_helper',
          'data' => is_array($servizio->getCoverage()) ? implode(
            ',',
            $servizio->getCoverage()
          ) : $servizio->getCoverage(),
          'required' => false,
        ]
      )
      ;
  }

  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => 'App\Entity\Servizio'
    ));

    $this->configureTranslationOptions($resolver);
  }

  public function getBlockPrefix()
  {
    return 'card_data';
  }
}
