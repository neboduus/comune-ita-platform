<?php


namespace App\Form\Admin\Servizio;


use App\Entity\Servizio;
use App\Form\I18n\AbstractI18nType;
use App\Form\I18n\I18nDataMapperInterface;
use App\Form\I18n\I18nTextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class CardDataType extends AbstractI18nType
{
  /**
   * @var TranslatorInterface
   */
  private $translator;

  /**
   * @param I18nDataMapperInterface $dataMapper
   * @param $locale
   * @param $locales
   * @param TranslatorInterface $translator
   */
  public function __construct(I18nDataMapperInterface $dataMapper, $locale, $locales, TranslatorInterface $translator)
  {
    parent::__construct($dataMapper, $locale, $locales);
    $this->translator = $translator;
  }
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    /** @var Servizio $servizio */
    $servizio = $builder->getData();

    // you can add the translatable fields
    $this->createTranslatableMapper($builder, $options)
      ->add("shortDescription", I18nTextareaType::class, [
        "label" => 'servizio.short_description',
        'required' => true,
        'purify_html' => true,
      ])
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
    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
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

  public function onPreSubmit(FormEvent $event)
  {
    $service = $event->getForm()->getData();
    $data = $event->getData();
    if ($data['shortDescription'][$this->getLocale()] === $service->getName())
    {
      $event->getForm()->addError(new FormError($this->translator->trans("servizio.change_short_description")));
    }
  }
}
