<?php


namespace AppBundle\Form\Api;

use AppBundle\Entity\Categoria;
use AppBundle\Services\Manager\CategoryManager;
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
use Symfony\Component\Translation\TranslatorInterface;

class CategoryApiType extends AbstractType
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
   * CategoryType constructor.
   * @param CategoryManager $categoryManager
   * @param TranslatorInterface $translator
   */
  public function __construct(CategoryManager $categoryManager, TranslatorInterface $translator)
  {
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

    $builder
      ->add('name', TextType::class, [
        'label' => 'general.nome',
        'required' => true
      ])
      ->add('description', TextareaType::class, [
        'label' => 'general.descrizione',
        'required' => false
      ])
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

    if (isset($data['parent_id']) && $category->getId() == $data['parent_id']) {
      $event->getForm()->addError(
        new FormError($this->translator->trans('categories.error_reference_parent'))
      );
    }

    $children = $this->categoryManager->getCategoryTree($category->getId());

    if (isset($data['parent_id']) && array_key_exists($data['parent_id'], $children)) {
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
  }
}
