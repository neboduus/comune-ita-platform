<?php

namespace AppBundle\Form\Admin\Servizio;

use AppBundle\Entity\FormIO;
use AppBundle\Entity\SciaPraticaEdilizia;
use AppBundle\Entity\Servizio;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use AppBundle\Services\FormServerApiAdapterService;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Form\FormError;


class FormIOTemplateType extends AbstractType
{
  /**
   * @var EntityManager
   */
  private $em;

  /**
   * @var FormServerApiAdapterService
   */
  private $formServerService;

  /**
   * FormIOTemplateType constructor.
   * @param EntityManager $entityManager
   * @param FormServerApiAdapterService $formServerService
   */
  public function __construct(EntityManager $entityManager, FormServerApiAdapterService $formServerService)
  {
    $this->em = $entityManager;
    $this->formServerService = $formServerService;
  }

  /**
   * @param FormBuilderInterface $builder
   * @param array $options
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    /** @var Servizio $servizio */
    $servizio = $builder->getData();
    $builder
      ->add('service_id', HiddenType::class,
        [
          'attr' => ['value' => $this->getFormIoId($servizio)],
          'mapped' => false,
          'required' => false,
        ])
      ->add('current_id', HiddenType::class,
        [
          'attr' => ['value' => $servizio->getId()],
          'mapped' => false,
          'required' => false,
        ]);
    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
    //$builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));
  }


  public function getBlockPrefix()
  {
    return 'formio_template';
  }

  public function onPreSubmit(FormEvent $event)
  {
    /** @var Servizio $servizio */
    $servizio = $event->getForm()->getData();

    if (isset($event->getData()['service_id']) && !empty($event->getData()['service_id'])) {

      if (!$this->getFormIoId($servizio)) {
        $serviceID = $event->getData()['service_id'];

        $response = false;
        if ( $serviceID == 'new' ) {
          $response = $this->formServerService->createForm($servizio);
        } else {
          $serviceToClone = $this->em->getRepository('AppBundle:Servizio')->find($serviceID);
          if ($serviceToClone instanceof Servizio) {
            $this->cloneService($servizio, $serviceToClone);
            $response = $this->formServerService->cloneForm($servizio, $serviceToClone);
          }
        }
        if ($response['status'] == 'success') {
          $formId = $response['form_id'];
          $additionalData = $servizio->getAdditionalData();
          $additionalData['formio_id'] = $formId;
          $servizio->setAdditionalData($additionalData);
        } else {
          $event->getForm()->addError(
            new FormError($response['message'])
          );
        }
      }
    } else {
        $event->getForm()->addError(
          new FormError('Devi selezionare almeno un template per continuare')
        );
    }
    $this->em->persist($servizio);
  }

  /**
   * @param SciaPraticaEdilizia $pratica
   * @param TestiAccompagnatoriProcedura $helper
   * @return array
   */
  private function setupHelperData(FormIO $pratica, TestiAccompagnatoriProcedura $helper)
  {
    return json_encode($pratica->getDematerializedForms());
  }

  private function cloneService(Servizio $service, Servizio $ServiceToClone)
  {

    $service->setName($ServiceToClone->getName() . " (copia)");
    $service->setDescription($ServiceToClone->getDescription() ?? '');

  }

  private function getFormIoId(Servizio $service)
  {
    $formID = false;
    $flowsteps = $service->getFlowSteps();
    $additionalData = $service->getAdditionalData();
    if (!empty($flowsteps)) {
      foreach ($flowsteps as $f) {
        if (isset($f['type']) && $f['type'] == 'formio' && isset($f['parameters']['formio_id']) && $f['parameters']['formio_id'] && !empty($f['parameters']['formio_id'])) {
          $formID = $f['parameters']['formio_id'];
          break;
        }
      }
    }
    // Retrocompatibilità
    if (!$formID) {
      $formID = isset($additionalData['formio_id']) ? $additionalData['formio_id'] : false;
    }
    return $formID;
  }
}
