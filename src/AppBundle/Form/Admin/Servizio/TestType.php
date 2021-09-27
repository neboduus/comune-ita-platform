<?php

namespace AppBundle\Form\Admin\Servizio;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;


class TestType extends AbstractType
{

  /**
   * @param FormBuilderInterface $builder
   * @param array $options
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {

  }

  public function getBlockPrefix()
  {
    return 'test';
  }
}
