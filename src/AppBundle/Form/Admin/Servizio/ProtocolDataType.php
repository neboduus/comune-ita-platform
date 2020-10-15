<?php


namespace AppBundle\Form\Admin\Servizio;


use AppBundle\Entity\ServiceGroup;
use AppBundle\Entity\Servizio;
use AppBundle\Form\Base\BlockQuoteType;
use AppBundle\FormIO\SchemaFactory;
use AppBundle\Services\ProtocolloService;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Translation\TranslatorInterface;


class ProtocolDataType extends AbstractType
{
  /**
   * @var Container
   */
  private $protocolloService;

  /**
   * @var EntityManager
   */
  private $em;

  /**
   * @var SchemaFactory
   */
  private $schemaFactory;

  /**
   * @var TranslatorInterface
   */
  private $translator;

  public function __construct(ProtocolloService $protocolloService, EntityManager $entityManager, SchemaFactory $schemaFactory, TranslatorInterface $translator)
  {
    $this->protocolloService = $protocolloService;
    $this->em = $entityManager;
    $this->schemaFactory = $schemaFactory;
    $this->translator = $translator;
  }

  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    /** @var Servizio $service */
    $service = $builder->getData();
    $configParameters = $this->protocolloService->getHandler()->getConfigParameters();

    $currentServiceParameters = $service->getProtocolloParameters();

    if ($service->isProtocolRequired()) {
      $builder
        ->add('document_object', TextareaType::class, [
          'label' => 'Oggetto del documento',
          'mapped' => false,
          'data' => isset($service->getAdditionalData()['document_object']) ? $service->getAdditionalData()['document_object'] : Servizio::DEFAULT_DOCUMENT_OBJECT
        ]);

      if ($service->getServiceGroup() && $service->getServiceGroup()->isRegisterInFolder()) {
        $builder
          ->add('folder_object', TextareaType::class, [
            'label' => 'Oggetto del fascicolo',
            'mapped' => false,
            'data' => isset($service->getServiceGroup()->getAdditionalData()['folder_object']) ? $service->getServiceGroup()->getAdditionalData()['folder_object'] : ServiceGroup::DEFAULT_FOLDER_OBJECT,
            'attr' => ['readonly'=>true]
          ]);
      } else {
        $builder
          ->add('folder_object', TextareaType::class, [
            'label' => 'Oggetto del fascicolo',
            'mapped' => false,
            'data' => isset($service->getAdditionalData()['folder_object']) ? $service->getAdditionalData()['folder_object'] : Servizio::DEFAULT_FOLDER_OBJECT
          ]);
      }
    }

    if ($configParameters) {
      $builder
        ->add('parameters_needed', BlockQuoteType::class, [
          'label' => 'Inserisci qui i parametri di configurazione del protocollo'
        ]);
      foreach ($configParameters as $key => $param) {
        if (is_array($param)) {

          $paramForm = $builder->create($key, FormType::class, [
            'mapped' => false,
            'label_attr' => ['class' => 'pb-4'],
          ]);

          foreach ($param as $subparam) {
            $paramForm
              ->add($subparam, TextType::class, [
                  'label' => 'protocollo.' . $key . '.' . $subparam,
                  'data' => isset($currentServiceParameters[$key][$subparam]) ? $currentServiceParameters[$key][$subparam] : '',
                  'mapped' => false,
                  'required' => true
                ]
              );
          }
          $builder->add($paramForm);
        } else {
          $builder
            ->add($param, TextType::class, [
                'label' => 'protocollo.' . $param,
                'data' => isset($currentServiceParameters[$param]) ? $currentServiceParameters[$param] : '',
                'mapped' => false,
                'required' => true
              ]
            );
        }

      }
    } else {
      $builder
        ->add('no_parameters_needed', BlockQuoteType::class, [
          'label' => 'Il protocollo attuale non prevede ulteriori parametri di configurazione'
        ]);
    }

    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
  }

  public function onPreSubmit(FormEvent $event)
  {
    /** @var Servizio $service */
    $service = $event->getForm()->getData();
    $data = $event->getData();
    /*$configParameters = $this->protocolloService->getHandler()->getConfigParameters();
    $parameters = [];

    if ($configParameters) {
      foreach ($configParameters as $param) {
        if (!isset($data[$param]) || empty($data[$param])) {
          $event->getForm()->addError(
            new FormError('Tutti i parametri sono obbligatori')
          );
        }
        $parameters[$param] = $data[$param];
      }
    }*/

    $schema = $this->schemaFactory->createFromFormId($service->getFormIoId(), false);
    $extraKeys = ["service", "application_id"];
    if ($service->getServiceGroup()) {
      $extraKeys[] = "service_group";
    }
    $additionalData = $service->getAdditionalData();

    # Oggetto documento protocollo
    $documentObject = $data['document_object'];
    preg_match_all('/%(.*?)%/', $documentObject, $matches);

    foreach ($matches[1] as $match) {
      if (!$schema->hasComponent($match) && !in_array($match, $extraKeys)) {
        $event->getForm()->addError(
          new FormError($this->translator->trans('protocollo.invalid_document_object_key', ['%key%' => $match]))
        );
      }
    }

    $additionalData['document_object'] = $documentObject;

    # Oggetto fascicolo protocollo
    $folderObject = $data['folder_object'];

    if ($service->getServiceGroup() && $service->getServiceGroup()->isRegisterInFolder()) {
      # Ricavo oggetto del fascicolo dal gruppo
      unset($additionalData['folder_object']);
    } else {
      # Verifico che tutte le chiavi siano presenti
      preg_match_all('/%(.*?)%/', $folderObject, $matches);

      foreach ($matches[1] as $match) {
        if (!$schema->hasComponent($match) && !in_array($match, $extraKeys)) {
          $event->getForm()->addError(
            new FormError($this->translator->trans('protocollo.invalid_document_object_key', ['%key%' => $match]))
          );
        }
      }
      $additionalData['folder_object'] = $folderObject;
    }

    $service->setAdditionalData($additionalData);

    $service->setProtocolloParameters($data);
    $this->em->persist($service);
  }


  public function getBlockPrefix()
  {
    return 'protocol_data';
  }

}
