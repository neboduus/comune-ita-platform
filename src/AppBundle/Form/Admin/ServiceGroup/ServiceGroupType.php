<?php


namespace AppBundle\Form\Admin\ServiceGroup;

use AppBundle\Entity\ServiceGroup;
use AppBundle\FormIO\SchemaFactory;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;


class ServiceGroupType extends AbstractType
{

  /**
   * @var TranslatorInterface
   */
  private $translator;

  /**
   * @var SchemaFactory
   */
  private $schemaFactory;

  public function __construct(SchemaFactory $schemaFactory, TranslatorInterface $translator)
  {
    $this->translator = $translator;
    $this->schemaFactory = $schemaFactory;
  }

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    /** @var ServiceGroup $serviceGroup */
    $serviceGroup = $builder->getData();

    $builder
      ->add('name', TextType::class, [
        'label' => 'Nome'
      ])
      ->add('slug', HiddenType::class, [])
      ->add('description', TextareaType::class, [
        'label' => 'Descrizione',
        'required' => false
      ])
      ->add('sticky', CheckboxType::class, [
        'label' => 'In evidenza?',
        'required' => false,
      ])
      ->add('register_in_folder', CheckboxType::class, [
        'label' => 'Protocollare all\'interno dello stesso fascicolo?',
        'required' => false
      ])
      ->add('folder_object', TextareaType::class, [
        'label' => 'Oggetto del fascicolo',
        'mapped' => false,
        'data' => isset($serviceGroup->getAdditionalData()['folder_object']) ? $serviceGroup->getAdditionalData()['folder_object'] : ServiceGroup::DEFAULT_FOLDER_OBJECT
      ]);

    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
  }

  public function onPreSubmit(FormEvent $event)
  {
    /** @var ServiceGroup $serviceGroup */
    $serviceGroup = $event->getForm()->getData();
    $data = $event->getData();

    $extraKeys = ["service_group"];
    $folderObject = $data['folder_object'];

    foreach ($serviceGroup->getServices() as $service) {
      $schema = $this->schemaFactory->createFromFormId($service->getFormIoId(), false);
      preg_match_all('/%(.*?)%/', $folderObject, $matches);
      foreach ($matches[1] as $match) {
        if (!$schema->hasComponent($match) && !in_array($match, $extraKeys)) {
          $event->getForm()->addError(
            new FormError($this->translator->trans('protocollo.invalid_folder_object_key', [
              '%key%' => $match,
              '%service%'=>$service->getName()
            ]))
          );
        }
      }
    }

    $additionalData = $serviceGroup->getAdditionalData();
    $additionalData['folder_object'] = $folderObject;
    $serviceGroup->setAdditionalData($additionalData);

  }

  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => 'AppBundle\Entity\ServiceGroup',
      'csrf_protection' => false
    ));
  }

  public function getBlockPrefix()
  {
    return 'app_bundle_service_group_type';
  }
}
