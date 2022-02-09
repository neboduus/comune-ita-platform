<?php


namespace AppBundle\Form\Admin\Servizio;


use AppBundle\Entity\Servizio;
use AppBundle\Form\I18n\AbstractI18nType;
use AppBundle\Form\I18n\I18nTextareaType;
use AppBundle\Form\I18n\I18nTextType;
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
          'class' => 'AppBundle\Entity\Categoria',
          'choice_label' => 'name',
          'label' => 'servizio.categoria',
        ]
      )
      ->add(
        'recipients',
        EntityType::class,
        [
          'class' => 'AppBundle\Entity\Recipient',
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
          'class' => 'AppBundle\Entity\GeographicArea',
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
      'data_class' => 'AppBundle\Entity\Servizio'
    ));

    $this->configureTranslationOptions($resolver);
  }

  public function getBlockPrefix()
  {
    return 'card_data';
  }
}
