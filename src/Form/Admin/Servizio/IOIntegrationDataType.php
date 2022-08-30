<?php


namespace App\Form\Admin\Servizio;

use App\Form\IOServiceParametersType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;


class IOIntegrationDataType extends AbstractType
{

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('io_service_parameters', IOServiceParametersType::class, [
        'label' => 'app_io.config_parameters',
      ]);
  }

  public function getBlockPrefix()
  {
    return 'io_integration_data';
  }
}
