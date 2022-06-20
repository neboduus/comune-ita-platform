<?php


namespace AppBundle\Form\Admin\ServiceGroup;

use AppBundle\Entity\ServiceGroup;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use AppBundle\Form\I18n\AbstractI18nType;
use AppBundle\Form\I18n\I18nDataMapperInterface;
use AppBundle\Form\I18n\I18nTextareaType;
use AppBundle\Form\I18n\I18nTextType;
use AppBundle\Services\Manager\CategoryManager;
use Symfony\Component\Translation\TranslatorInterface;


class ServiceGroupType extends AbstractI18nType
{
  /**
   * @var CategoryManager
   */
  private $categoryManager;
  /**
   * @var TranslatorInterface
   */
  private $translator;

  /**
   * @param I18nDataMapperInterface $dataMapper
   * @param $locale
   * @param $locales
   * @param CategoryManager $categoryManager
   * @param TranslatorInterface $translator
   */
  public function __construct(I18nDataMapperInterface $dataMapper, $locale, $locales, CategoryManager $categoryManager, TranslatorInterface $translator)
  {
    parent::__construct($dataMapper, $locale, $locales);
    $this->categoryManager = $categoryManager;
    $this->translator = $translator;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    /** @var ServiceGroup $serviceGroup */
    $serviceGroup = $builder->getData();

    $this->createTranslatableMapper($builder, $options)
      ->add("name", I18nTextType::class, [
        "label" => 'gruppo_di_servizi.nome'
      ])
      ->add("description", I18nTextareaType::class, [
        "label" => 'gruppo_di_servizi.descrizione',
        'required' => false,
        'purify_html' => true,
      ])
      ->add('who', I18nTextareaType::class, [
        'label' => 'gruppo_di_servizi.a_chi_si_rivolge',
        'required' => false,
        'purify_html' => true,
      ])
      ->add('howto', I18nTextareaType::class, [
        'label' => 'gruppo_di_servizi.accedere',
        'required' => false,
        'purify_html' => true,
      ])
      ->add('specialCases', I18nTextareaType::class, [
        'label' => 'gruppo_di_servizi.casi_particolari',
        'required' => false,
        'purify_html' => true,
      ])
      ->add('moreInfo', I18nTextareaType::class, [
        'label' => 'gruppo_di_servizi.maggiori_info',
        'required' => false,
        'purify_html' => true,
      ]);
    
    $builder
      ->add('sticky', CheckboxType::class, [
        'label' => 'gruppo_di_servizi.in_evidenza',
        'required' => false,
      ])
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
        'geographic_areas',
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
      ->add('coverage', TextType::class, [
        'label' => 'gruppo_di_servizi.copertura_helper',
        'data' => is_array($serviceGroup->getCoverage()) ? implode(',', $serviceGroup->getCoverage()) : $serviceGroup->getCoverage(),
        'required' => false
      ])
      ->add('register_in_folder', CheckboxType::class, [
        'label' => 'gruppo_di_servizi.protocolla_in_fascicolo',
        'required' => false
      ]);
  }

  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => ServiceGroup::class,
      'csrf_protection' => false
    ));
    $this->configureTranslationOptions($resolver);
  }

  public function getBlockPrefix()
  {
    return 'app_bundle_service_group_type';
  }
}
