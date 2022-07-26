<?php


namespace AppBundle\Form\Admin\Servizio;


use AppBundle\BackOffice\SubcriptionPaymentsBackOffice;
use AppBundle\BackOffice\SubcriptionsBackOffice;
use AppBundle\Entity\Ente;
use AppBundle\Entity\Pratica;
use AppBundle\BackOffice\BackOfficeInterface;
use AppBundle\Entity\Servizio;
use AppBundle\Entity\SubscriptionService;
use AppBundle\Services\BackOfficeCollection;
use AppBundle\Services\FormServerApiAdapterService;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\Translation\TranslatorInterface;


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

  /**
   * @var TranslatorInterface $translator
   */
  private $translator;

  /**
   * @var BackOfficeCollection
   */
  private $backOfficeCollection;

  public function __construct(TranslatorInterface $translator, Container $container, EntityManagerInterface $entityManager, FormServerApiAdapterService $formServerService, BackOfficeCollection $backOffices)
  {
    $this->container = $container;
    $this->em = $entityManager;
    $this->formServerService = $formServerService;
    $this->translator = $translator;
    $this->backOfficeCollection = $backOffices;
  }

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $statuses = BackOfficeInterface::INTEGRATION_STATUSES;

    /** @var Servizio $service */
    $service = $builder->getData();
    /** @var Ente $ente */
    $ente = $service->getEnte();

    $backOffices = [];
    /** @var BackOfficeInterface $b */
    foreach ( $this->backOfficeCollection->getBackOffices() as $b ) {
      if (in_array($b->getPath(), $ente->getBackofficeEnabledIntegrations())) {
        $backOffices[$b->getName()] = get_class($b);
      }
    }

    $integrations = $service->getIntegrations();

    $selectedIntegration = 0;
    if (!empty($integrations)) {
      $selectedIntegration = array_keys($integrations)[0];
    }

    $builder
      ->add('trigger', ChoiceType::class, [
        'data' => $selectedIntegration,
        'label' => 'backoffice.integration.activation_point',
        'choices' => $statuses,
        'mapped' => false
      ])
      ->add('action', ChoiceType::class, [
        'label' => 'backoffice.integration.action_execute',
        'data' => $selectedIntegration != 0 ? $integrations[$selectedIntegration] : null,
        'choices' => $backOffices,
        'mapped' => false,
        'attr' => ['class' => 'backoffice-form-type'],
      ]);

    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
  }

  public function onPreSubmit(FormEvent $event)
  {
    /** @var Servizio $service */
    $service = $event->getForm()->getData();
    $data = $event->getData();

    if (isset($data['trigger']) && $data['trigger']) {
      $oldIntegration = $service->getIntegrations() ? array_values($service->getIntegrations())[0] : null;
      if($oldIntegration && $oldIntegration !== $data['action']) {
        // Integration changed
        if ($oldIntegration == SubcriptionsBackOffice::class || SubcriptionPaymentsBackOffice::class) {
          $sql = 'SELECT DISTINCT id FROM subscription_service, json_to_recordset(subscription_service.payments) as x("payment_service" text) WHERE payment_service = ?';
          try {
            $stmt = $this->em->getConnection()->executeQuery($sql, [$service->getId()]);
            $countRelated = $stmt->rowCount();
            if ($countRelated > 0) {
              $event->getForm()->addError(
                new FormError($this->translator->trans('backoffice.integration.related_payments_error', ['%num%' => $countRelated ])),
              );
            }
          } catch (Exception $e) {
            $event->getForm()->addError(
              new FormError($this->translator->trans('backoffice.integration.edit_integration_error')),
            );
          }

        }
      }

      $service->setIntegrations([
        $data['trigger'] => $data['action']
      ]);
      $this->em->persist($service);

      $formSchema = $this->formServerService->getFormSchema($this->formServerService->getFormIdFromService($service));
      /** @var BackOfficeInterface $backOfficeHandler */
      $backOfficeHandler = $this->container->get($data['action']);

      if (isset($data["action"]) && !in_array($data["trigger"], $backOfficeHandler->getAllowedActivationPoints())) {
        $event->getForm()->addError(
          new FormError($this->translator->trans('backoffice.integration.invalid_activation_point')),
        );
      }

      $flatSchema = $this->arrayFlat($formSchema['schema']);

      $errors = $backOfficeHandler->checkRequiredFields($flatSchema);
      if ($errors) {
        foreach ($errors as $type => $integrationType) {
          foreach ($integrationType as $key=>$error) {
            $event->getForm()->addError(
              new FormError($error)
            );
          }
          if (array_key_last($errors) != $type)
            $event->getForm()->addError(
              new FormError($this->translator->trans('backoffice.integration.or')),
              );
        }
      }
    } else {
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
