<?php


namespace AppBundle\Form\Admin\Servizio;

use AppBundle\Form\IOServiceParametersType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;


class IOIntegrationDataType extends AbstractType
{

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('io_service_parameters', IOServiceParametersType::class, [
        'label' => 'Parametri di configurazione per l\'integrazione con App IO',
      ]);
  }

  public function getBlockPrefix()
  {
    return 'io_integration_data';
  }
}
