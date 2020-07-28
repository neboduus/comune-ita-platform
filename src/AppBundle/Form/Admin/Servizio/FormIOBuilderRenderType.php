<?php

namespace AppBundle\Form\Admin\Servizio;

use AppBundle\Entity\Servizio;
use AppBundle\FormIO\SchemaFactory;
use AppBundle\FormIO\SchemaFactoryInterface;
use AppBundle\Model\FlowStep;
use AppBundle\Model\FormIOFlowStep;
use AppBundle\Services\FormServerApiAdapterService;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;


class FormIOBuilderRenderType extends AbstractType
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
   * @var SchemaFactory
   */
  private $schemaFactory;

  /**
   * ChooseAllegatoType constructor.
   * @param EntityManager $entityManager
   * @param FormServerApiAdapterService $formServerService
   * @param SchemaFactoryInterface $schemaFactory
   */
  public function __construct(EntityManager $entityManager, FormServerApiAdapterService $formServerService, SchemaFactoryInterface $schemaFactory)
  {
    $this->em = $entityManager;
    $this->formServerService = $formServerService;
    $this->schemaFactory = $schemaFactory;
  }

  /**
   * @param FormBuilderInterface $builder
   * @param array $options
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    /** @var Servizio $servizio */
    $servizio = $builder->getData();
    $formId = $servizio->getFormIoId();
    $builder
      ->add('form_id', HiddenType::class,
        [
          'attr' => ['value' => $formId],
          'mapped' => false,
          'required' => false,
        ]
      )
      ->add('form_schema', HiddenType::class,
        [
          'attr' => ['value' => ''],
          'mapped' => false,
          'required' => false,
        ]
      );
    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
  }

  public function onPreSubmit(FormEvent $event)
  {
    /** @var Servizio $servizio */
    $servizio = $event->getForm()->getData();

    if (isset($event->getData()['form_schema']) && !empty($event->getData()['form_schema'])) {
      $schema = \json_decode($event->getData()['form_schema'], true);
      $response = $this->formServerService->editForm($schema);
      if ($response['status'] != 'success') {
        $event->getForm()->addError(
          new FormError($response['message'])
        );
      } else {
        $formId = $response['form_id'];
        $flowStepList = $servizio->getFlowSteps();
        $flowSteps = [];
        foreach ($flowStepList as $flowStep) {
          $flowStep = FlowStep::fromArray($flowStep);
          if ($flowStep->getIdentifier() == $formId) {
            $schema = $this->schemaFactory->createFromFormId($formId, false);
            $flowStep = new FormIOFlowStep($formId, $schema->toArray());
          }
          $flowSteps[] = $flowStep;
        }
        $servizio->setFlowSteps($flowSteps);
      }
    }

    $this->em->persist($servizio);
  }

  public function getBlockPrefix()
  {
    return 'formio_builder_render';
  }

}
