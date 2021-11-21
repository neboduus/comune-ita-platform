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

    $statuses = [
      'Bozza' => Servizio::STATUS_CANCELLED,
      'Pubblicato' => Servizio::STATUS_AVAILABLE,
      'Non attivo' => Servizio::STATUS_SUSPENDED,
      'Privato' => Servizio::STATUS_PRIVATE,
      'Programmato' => Servizio::STATUS_SCHEDULED,
    ];

    $accessLevels = [
      'Anonimo' => Servizio::ACCESS_LEVEL_ANONYMOUS,
      'Social' => Servizio::ACCESS_LEVEL_SOCIAL,
      'Spid livello 1' => Servizio::ACCESS_LEVEL_SPID_L1,
      'Spid livello 2' => Servizio::ACCESS_LEVEL_SPID_L2,
      'Cie' => Servizio::ACCESS_LEVEL_CIE,
    ];

    $legacyAccessLevels = [
      'Social' => Servizio::ACCESS_LEVEL_SOCIAL,
      'Spid livello 1' => Servizio::ACCESS_LEVEL_SPID_L1,
      'Spid livello 2' => Servizio::ACCESS_LEVEL_SPID_L2,
      'Cie' => Servizio::ACCESS_LEVEL_CIE,
    ];

    $workflows = [
      'Approvazione' => Servizio::WORKFLOW_APPROVAL,
      'Inoltro' => Servizio::WORKFLOW_FORWARD,
    ];

    /** @var Servizio $servizio */
    $servizio = $builder->getData();


    // you can add the translatable fields
    $this->createTranslatableMapper($builder, $options)
      ->add("description", I18nTextareaType::class, [
        "label" => 'servizio.cos_e'
      ])
      ->add("who", I18nTextareaType::class, [
        "label" => 'servizio.a_chi_si_rivolge'
      ])
      ->add("howto", I18nTextareaType::class, [
        "label" => 'servizio.accedere'
      ])
      ->add("specialCases", I18nTextareaType::class, [
        "label" => 'servizio.casi_particolari'
      ])
      ->add("moreInfo", I18nTextareaType::class, [
        "label" => 'servizio.maggiori_info'
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
