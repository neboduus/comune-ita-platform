<?php

namespace AppBundle\Form\FormIO;

use AppBundle\Entity\Allegato;
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



class FormIOAnonymousRenderType extends AbstractType
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
   * @var
   */
  private $schema = false;

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
    $formID = $pratica->getServizio()->getFormIoId();

    $result = $this->formServerService->getFormSchema($pratica->getServizio()->getFormIoId());
    if ($result['status'] == 'success') {
      $this->schema = $result['schema'];
    }

    /** @var TestiAccompagnatoriProcedura $helper */
    $helper = $options["helper"];
    $helper->setStepTitle('steps.scia.modulo_default.label', true);

    $data = '';
    if ($pratica->getDematerializedForms()) {
      $data = \json_encode($pratica->getDematerializedForms());
    };

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
  }


  public function getBlockPrefix()
  {
    return 'formio_render';
  }

  public function onPreSubmit(FormEvent $event)
  {

    /** @var Pratica $pratica */
    $pratica = $event->getForm()->getData();
    $compiledData = $flattenedData = array();
    if (isset($event->getData()['dematerialized_forms'])) {
      $data = json_decode($event->getData()['dematerialized_forms'], true);
      $flattenedData = $this->arrayFlat($data);
      $compiledData = $data;
    }

    $pratica->setDematerializedForms(
      array(
        'data' => $compiledData,
        'flattened' => $flattenedData,
        'schema' => $this->arrayFlat($this->schema, true)
      )
    );

    // Associo gli allegati alla pratica
    foreach ($flattenedData as $key => $value) {
      if ( isset($this->schema[$key]['type']) && $this->schema[$key]['type'] == 'file') {
        foreach ($value as $file) {
          $id = $file['data']['id'];
          $attachment = $this->em->getRepository('AppBundle:Allegato')->find($id);
          if ($attachment instanceof Allegato) {
            $attachments[]= $id;
            $pratica->addAllegato($attachment);
          }
        }
      }
    }
  }

  /**
   * @param SciaPraticaEdilizia $pratica
   * @return false|string
   */

  /**
   * @param $array
   * @param string $prefix
   * @return array
   */
  private function arrayFlat($array, $isSchema = false, $prefix = '')
  {
    $result = array();
    foreach ($array as $key => $value) {

      if ($key === 'metadata' || $key === 'state') {
        continue;
      }

      $isFile = false;
      if ( !$isSchema && isset($this->schema[$key]['type']) &&
        ( $this->schema[$key]['type'] == 'file' || $this->schema[$key]['type'] == 'financial_report') )  {
        $isFile = true;
      }
      $new_key = $prefix . (empty($prefix) ? '' : '.') . $key;

      if (is_array($value) && !$isFile) {
        $result = array_merge($result, $this->arrayFlat($value, $isSchema, $new_key));
      } else {
        $result[$new_key] = $value;
      }
    }
    return $result;
  }

}
