<?php

namespace AppBundle\Form\FormIO;

use AppBundle\Entity\FormIO;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\SciaPraticaEdilizia;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use AppBundle\Services\FormServerApiAdapterService;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Form\FormError;


class FormIORenderType extends AbstractType
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
   * FormIORenderType constructor.
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

    /** @var FormIO $pratica */
    $pratica = $builder->getData();

    $formID = $this->getFormIoId($pratica);

    /** @var TestiAccompagnatoriProcedura $helper */
    $helper = $options["helper"];
    $helper->setStepTitle('steps.scia.modulo_default.label', true);
    $data = $this->setupHelperData($pratica, $helper);
    $builder
      ->add('form_id', HiddenType::class,
        [
          'attr' => ['value' => $formID],
          'mapped' => false,
          'required' => false,
        ]
      )
      ->add('dematerialized_forms', HiddenType::class,
        [
          'attr' => ['value' => $data],
          'mapped' => false,
          'required' => false,
        ]
      );
    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
    //$builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));
  }


  public function getBlockPrefix()
  {
    return 'formio_render';
  }

  public function onPreSubmit(FormEvent $event)
  {

    $options = $event->getForm()->getConfig()->getOptions();
    /** @var TestiAccompagnatoriProcedura $helper */
    $helper = $options["helper"];

    /** @var SciaPraticaEdilizia $pratica */
    $pratica = $event->getForm()->getData();
    $compiledData = array();
    if (isset($event->getData()['dematerialized_forms'])) {
      $data = json_decode($event->getData()['dematerialized_forms'], true);
      $flattenedData = $this->arrayFlat($data);
      $compiledData = $data;
    }

    $schema = false;
    $result = $this->formServerService->getFormSchema($this->getFormIoId($pratica));
    if ($result['status'] == 'success') {
      $schema = $this->arrayFlat($result['schema']);
    }

    $pratica->setDematerializedForms(
      array(
        'data'      => $compiledData,
        'flattened' => $flattenedData,
        'schema'    => $schema
      )
    );
    $this->em->persist($pratica);
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

  private function getFormIoId(Pratica $pratica)
  {
    $formID = false;
    $flowsteps = $pratica->getServizio()->getFlowSteps();
    $additionalData = $pratica->getServizio()->getAdditionalData();
    if (!empty($flowsteps)) {
      foreach ($flowsteps as $f) {
        if ($f['type'] == 'formio' && $f['parameters']['formio_id'] && !empty($f['parameters']['formio_id'])) {
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
}
