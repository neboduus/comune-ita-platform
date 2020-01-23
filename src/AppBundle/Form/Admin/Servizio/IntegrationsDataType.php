<?php


namespace AppBundle\Form\Admin\Servizio;


use AppBundle\Entity\Pratica;
use AppBundle\BackOffice\BackOfficeInterface;
use AppBundle\Entity\Servizio;
use AppBundle\Services\FormServerApiAdapterService;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;


class IntegrationsDataType extends AbstractType
{

  /**
   * @var Container
   */
  private $container;

  /**
   * @var EntityManager
   */
  private $em;

  /**
   * @var FormServerApiAdapterService
   */
  private $formServerService;

  public function __construct(Container $container, EntityManager $entityManager, FormServerApiAdapterService $formServerService)
  {
    $this->container = $container;
    $this->em = $entityManager;
    $this->formServerService = $formServerService;
  }

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $statuses = [
      'Nessuna integrazione prevista' => 0,
      'Pratica pagata'                => Pratica::STATUS_PAYMENT_SUCCESS,
      'Pratica inviata'               => Pratica::STATUS_SUBMITTED,
      'Pratica protocollata'          => Pratica::STATUS_REGISTERED,
      'Pratica presa in carico'       => Pratica::STATUS_PENDING,
      'Pratica accettata'             => Pratica::STATUS_COMPLETE,
      'Pratica rifiutata'             => Pratica::STATUS_CANCELLED
    ];

    $backOffices = [
      'Aggiungi iscrizione' => "AppBundle\BackOffice\SubcriptionsBackOffice"
    ];

    /** @var Servizio $service */
    $service = $builder->getData();
    $integrations = $service->getIntegrations();

    $selectedIntegration = 0;
    if (!empty($integrations)) {
      $selectedIntegration = array_keys($integrations)[0];
    }

    $builder
      ->add('trigger', ChoiceType::class, [
        'data'    => $selectedIntegration,
        'label'   => 'Punto di attivazione',
        'choices' => $statuses,
        'mapped'  => false
      ])
      ->add('action', ChoiceType::class, [
        'label' => 'Azione da eseguire',
        'choices' => $backOffices,
        'mapped' => false,
        'attr' => ['class' => 'backoffice-form-type'],
        'disabled' => $selectedIntegration == 0
      ]);

    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
  }

  public function onPreSubmit(FormEvent $event)
  {
    /** @var Servizio $service */
    $service = $event->getForm()->getData();
    $data = $event->getData();

    if (isset($data['trigger']) && $data['trigger']) {
      $service->setIntegrations([
        $data['trigger'] => $data['action']
      ]);
      $this->em->persist($service);

      $formSchema = $this->formServerService->getFormSchema($this->formServerService->getFormIdFromService($service));
      /** @var BackOfficeInterface $backOfficeHandler */
      $backOfficeHandler = $this->container->get($data['action']);

      $flatSchema = $this->arrayFlat($formSchema['schema']);

      foreach ($backOfficeHandler->getRequiredFields() as $field) {
        if (!array_key_exists($field . '.label', $flatSchema)) {
          $event->getForm()->addError(
            new FormError('Il campo "' . $field . '" deve essere presente nel form per poter attivare questa integrazione.')
          );
        }
      }
    }else {
      $service->setIntegrations(null);
      $this->em->persist($service);
    }
  }

  private function arrayFlat($array, $prefix = '')
  {



    $result = array();
    foreach ($array as $key => $value) {
      if ($key == 'metadata' || $key == 'state') {
        continue;
      }
      $new_key = $prefix . (empty($prefix) ? '' : '.') . $key;

      if (is_array($value)) {
        $result = array_merge($result, $this->arrayFlat($value, $new_key));
      } else {
        $result[$new_key] = $value;
      }
    }
    return $result;
  }


  public function getBlockPrefix()
  {
    return 'integrations_data';
  }
}
