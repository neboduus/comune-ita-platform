<?php

namespace AppBundle\Form\I18n;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


/**
 *
 * stof_doctrine_extensions:
 * default_locale: %locale%
 * translation_fallback: true
 * persist_default_translation: true
 * orm:
 * default:
 * translatable: true
 *
 * Class AbstractType
 * @package Simettric\DoctrineTranslatableFormBundle\Form
 */
abstract class AbstractI18nType extends AbstractType
{

  private $locales = [];

  private $locale;

  /**
   * @var I18nDataMapperInterface
   */
  private $mapper;


  /**
   * AbstractI18nType constructor.
   * @param I18nDataMapperInterface $dataMapper
   * @param $locale
   * @param $locales
   */
  function __construct(I18nDataMapperInterface $dataMapper, $locale, $locales)
  {
    $this->mapper = $dataMapper;
    $this->locales = explode('|', $locales);
    $this->locale = $locale;
  }


  public function setRequiredLocale($iso)
  {
    $this->required_locale = $iso;
  }

  public function setLocales(array $locales)
  {
    $this->locales = $locales;
  }


  /**
   * @param FormBuilderInterface $builderInterface
   * @param array $options
   * @return I18nDataMapperInterface
   */
  protected function createTranslatableMapper(FormBuilderInterface $builderInterface, array $options)
  {
    $this->mapper->setBuilder($builderInterface);
    $this->mapper->setLocales($options["locales"]);
    $this->mapper->setRequiredLocale($options["required_locale"]);
    $builderInterface->setDataMapper($this->mapper);

    return $this->mapper;
  }


  protected function configureTranslationOptions(OptionsResolver $resolver)
  {

    $resolver->setRequired(["locales", "required_locale"]);

    $data = [
      'locales' => $this->locales ?: ["it"],
      "required_locale" => $this->locale ?: "it",
    ];
    $resolver->setDefaults($data);
  }

}
