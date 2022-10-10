<?php


namespace App\Form\Admin;

use App\Entity\Categoria;
use App\Entity\Servizio;
use App\Form\I18n\AbstractI18nType;
use App\Form\I18n\I18nDataMapperInterface;
use App\Form\I18n\I18nTextareaType;
use App\Form\I18n\I18nTextType;
use App\Services\Manager\CategoryManager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class CategoryType extends AbstractI18nType
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

    /** @var Categoria $category */
    $category = $builder->getData();

    $categories = [];
    $categoryTree = $this->categoryManager->getCategoryTree();
    foreach ($categoryTree as $c) {
      $categories[$c['spaced_name']] = $c['id'];
    }

    $this->createTranslatableMapper($builder, $options)
      ->add("name", I18nTextType::class, [
        "label" => 'general.nome'
      ])
      ->add("description", I18nTextareaType::class, [
        "label" => 'general.descrizione'
      ]);

    $builder
      ->add('parent', HiddenType::class, [
        'empty_data' => null
      ])
      ->add('parent_id', ChoiceType::class,
        [
          'required' => false,
          'mapped' => false,
          'data' => $category->getParentId(),
          'label' => 'categories.parent',
          'choices' => $categories
        ]
      )
    ;

    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
  }

  public function onPreSubmit(FormEvent $event)
  {
    /** @var Categoria $category */
    $category = $event->getForm()->getData();
    $data = $event->getData();

    if ($category->getId() == $data['parent_id']) {
      $event->getForm()->addError(
        new FormError($this->translator->trans('categories.error_reference_parent'))
      );
    }

    $children = $this->categoryManager->getCategoryTree($category->getId());

    if (array_key_exists($data['parent_id'], $children)) {
      $event->getForm()->addError(
        new FormError($this->translator->trans('categories.error_reference_children'))
      );
    }

    if (empty($data['parent_id'])) {
      $data['parent'] = null;
    } else {
      $data['parent'] = $this->categoryManager->get($data['parent_id']);
    }
    $event->setData($data);

  }

  /**
   * {@inheritdoc}
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => Categoria::class,
      'allow_extra_fields' => true,
      'csrf_protection' => false
    ));
    $this->configureTranslationOptions($resolver);
  }
}
