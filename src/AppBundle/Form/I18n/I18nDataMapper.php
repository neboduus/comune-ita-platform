<?php
/**
 * Created by Asier Marqués <asiermarques@gmail.com>
 * Date: 17/5/16
 * Time: 20:58
 */

namespace AppBundle\Form\I18n;


use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Translatable\TranslatableListener;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Gedmo\Translatable\Entity\Repository\TranslationRepository;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Exception;

class I18nDataMapper implements I18nDataMapperInterface
{


  /**
   * @var EntityManagerInterface
   */
  private $em;

  /**
   * @var TranslationRepository
   */
  private $repository;

  /**
   * @var FormBuilderInterface
   */
  private $builder;

  private $translations = [];

  private $locales = [];

  private $required_locale;

  private $property_names = [];


  public function __construct(EntityManagerInterface $entityManager, TranslationRepository $repository = null)
  {

    $this->em = $entityManager;

    if (!$repository) {
      $repository = 'Gedmo\Translatable\Entity\Translation';
    }

    $this->repository = $this->em->getRepository($repository);

  }

  public function setBuilder(FormBuilderInterface $builderInterface)
  {
    $this->builder = $builderInterface;
  }

  public function setRequiredLocale($locale)
  {
    $this->required_locale = $locale;
  }

  public function setLocales(array $locales)
  {
    $this->locales = $locales;
  }

  public function getLocales()
  {
    return $this->locales;
  }

  public function getTranslations($entity)
  {

    if (!count($this->translations)) {
      $this->translations = $this->repository->findTranslations($entity);
    }

    return $this->translations;

  }


  /**
   * @param $name
   * @param $type
   * @param array $options
   * @return I18nDataMapper
   * @throws \Exception
   */
  public function add($name, $type, $options = [])
  {

    $this->property_names[] = $name;
    $field = $this->builder
      ->add($name, $type)
      ->get($name);

    if (!$field->getType()->getInnerType() instanceof I18nFieldInterface) {
      throw new \Exception("{$name} must implement I18nFieldInterface");
    }

    foreach ($this->locales as $iso) {

      $options = [
        //"label" => $iso,
        "label" => $options["label"] ?? $iso,
        "attr" => $options["attr"] ?? [],
        //"required" => ($iso == $this->required_locale && (!isset($options["required"]) || $options["required"])),
        "required" => ($iso == $this->required_locale && (isset($options["required"]) && $options["required"])),
      ];

      $field->add($iso, get_class($field->getType()->getParent()->getInnerType()), $options);

    }

    return $this;

  }


  /**
   * Maps properties of some data to a list of forms.
   *
   * @param mixed $data Structured data.
   * @param FormInterface[] $forms A list of {@link FormInterface} instances.
   *
   * @throws Exception\UnexpectedTypeException if the type of the data parameter is not supported.
   */
  public function mapDataToForms($data, $forms)
  {

    $accessor = PropertyAccess::createPropertyAccessor();

    foreach ($forms as $form) {
      $this->translations = [];
      $translations = $this->getTranslations($data);

      // Fix per traduzione vecchi oggetti
      if (empty($translations[$this->required_locale][$form->getName()]) && $accessor->isReadable($data, $form->getName()) && !empty($accessor->getValue($data, $form->getName()))) {
        $translations[$this->required_locale][$form->getName()] = $accessor->getValue($data, $form->getName());
      }

      if (false !== in_array($form->getName(), $this->property_names)) {
        $values = [];
        foreach ($this->getLocales() as $iso) {
          if (isset($translations[$iso])) {
            $values[$iso] = isset($translations[$iso][$form->getName()]) ? $translations[$iso][$form->getName()] : "";
            // Don't decode default language
            if ($form->getConfig()->getType()->getInnerType() instanceof I18nJsonType  && $iso !== $this->required_locale) {
              $values[$iso] = json_decode($values[$iso]);
            }
          }
        }
        $form->setData($values);
      } else {
        if (false === $form->getConfig()->getOption("mapped") || null === $form->getConfig()->getOption("mapped")) {
          continue;
        }
        $form->setData($accessor->getValue($data, $form->getName()));
      }
    }
  }

  /**
   * Maps the data of a list of forms into the properties of some data.
   *
   * @param FormInterface[] $forms A list of {@link FormInterface} instances.
   * @param mixed $data Structured data.
   *
   * @throws \Exception
   */
  public function mapFormsToData($forms, &$data)
  {
    foreach ($forms as $form) {

      $entityInstance = $data;

      if (false !== in_array($form->getName(), $this->property_names)) {

        $meta = $this->em->getClassMetadata(get_class($entityInstance));
        $listener = new TranslatableListener();
        $listener->loadMetadataForObjectClass($this->em, $meta);
        $config = $listener->getConfiguration($this->em, $meta->name);

        $translations = $form->getData();
        foreach ($this->getLocales() as $iso) {
          if (isset($translations[$iso])) {
            if (isset($config['translationClass'])) {
              $t = $this->em->getRepository($config['translationClass'])
                ->translate($entityInstance, $form->getName(), $iso, $translations[$iso]);
              $this->em->persist($entityInstance);
              $this->em->flush();
            } else {
              $this->repository->translate($entityInstance, $form->getName(), $iso, $translations[$iso]);
            }
          }
        }
      } else {

        if (false === $form->getConfig()->getOption("mapped") || null === $form->getConfig()->getOption("mapped")) {
          continue;
        }

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($entityInstance, $form->getName(), $form->getData());

      }
    }
  }


}
