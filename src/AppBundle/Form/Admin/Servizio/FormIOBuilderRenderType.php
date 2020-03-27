<?php

namespace AppBundle\Form\Admin\Servizio;

use AppBundle\Entity\FormIO;
use AppBundle\Entity\SciaPraticaEdilizia;
use AppBundle\Entity\Servizio;
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
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\GuzzleException;


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
   * ChooseAllegatoType constructor.
   *
   * @param EntityManager $entityManager
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
      }
    }

    $this->em->persist($servizio);
  }

  public function getBlockPrefix()
  {
    return 'formio_builder_render';
  }

}
