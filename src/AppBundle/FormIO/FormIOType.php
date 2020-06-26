<?php

namespace AppBundle\FormIO;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormIOType extends AbstractType
{
  /**
   * @var SchemaFactoryInterface
   */
  private $schemaFactory;

  public function __construct(SchemaFactoryInterface $schemaFactory)
  {
    $this->schemaFactory = $schemaFactory;
  }

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $formIOId = $options['formio'];

    $formIOSchema = $this->schemaFactory->createFromFormId($formIOId);

    foreach ($formIOSchema->getComponents() as $component) {
      if (empty($options['formio_validate_fields']) || in_array($component['name'], $options['formio_validate_fields'])) {
        $builder->add($component->getFormName(), $component->getFormType(), $component->getFormOptions());
      }
    }
  }

  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(
      array(
        'formio' => null,
        'formio_validate_fields' => [],
        'csrf_protection' => false,
        'validation_groups' => ['Default'],
      )
    );
  }

  public function getBlockPrefix()
  {
    return 'formio';
  }

}
