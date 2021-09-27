<?php

namespace AppBundle\Form\I18n;

use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormBuilderInterface;

interface I18nDataMapperInterface extends DataMapperInterface
{

  public function setBuilder(FormBuilderInterface $builderInterface);

  public function add($name, $type, $options = []);

  public function setLocales(array $locales);

  public function getLocales();

  public function setRequiredLocale($locale);

}
