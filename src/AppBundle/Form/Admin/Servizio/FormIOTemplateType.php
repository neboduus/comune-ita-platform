<?php

namespace AppBundle\Form\Admin\Servizio;

use AppBundle\Entity\FormIO;
use AppBundle\Entity\SciaPraticaEdilizia;
use AppBundle\Entity\Servizio;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use AppBundle\FormIO\SchemaFactory;
use AppBundle\FormIO\SchemaFactoryInterface;
use AppBundle\Model\FlowStep;
use AppBundle\Model\FormIOFlowStep;
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
   * @var SchemaFactory
   */
  private $schemaFactory;

  /**
   * FormIOTemplateType constructor.
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
    $builder
      ->add('service_id', HiddenType::class,
        [
          'attr' => ['value' => $servizio->getFormIoId()],
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

  public function onPreSubmit(FormEvent $event)
  {
    /** @var Servizio $servizio */
    $servizio = $event->getForm()->getData();

    if (isset($event->getData()['service_id']) && !empty($event->getData()['service_id'])) {

      if ( empty($servizio->getFormIoId()) ) {
        $serviceID = $event->getData()['service_id'];

        $response = false;
        if ( $serviceID == 'new' ) {
          $response = $this->formServerService->createForm($servizio);
        } else {
          $serviceToClone = $this->em->getRepository('AppBundle:Servizio')->find($serviceID);
          if ($serviceToClone instanceof Servizio) {
            $servizio->setName($serviceToClone->getName() . " (copia)");
            $servizio->setDescription($serviceToClone->getDescription() ?? '');
            $response = $this->formServerService->cloneForm($serviceToClone);
          }
        }

        if ($response['status'] == 'success') {

          $formId = $response['form_id'];
          $schema = $this->schemaFactory->createFromFormId($formId);
          $flowStep = new FormIOFlowStep($formId, $schema->toArray());

          $servizio->setFlowSteps([$flowStep]);

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

  public function getBlockPrefix()
  {
    return 'formio_template';
  }
}
