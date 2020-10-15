<?php

namespace AppBundle\Form\Admin\Servizio;

use AppBundle\Entity\FormIO;
use AppBundle\Entity\SciaPraticaEdilizia;
use AppBundle\Entity\ServiceGroup;
use AppBundle\Entity\Servizio;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use AppBundle\FormIO\SchemaFactory;
use AppBundle\Services\FormServerApiAdapterService;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Translation\TranslatorInterface;
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
   * @var SchemaFactory
   */
  private $schemaFactory;
  /**
   * @var TranslatorInterface
   */
  private $translator;

  /**
   * ChooseAllegatoType constructor.
   *
   * @param EntityManager $entityManager
   * @param FormServerApiAdapterService $formServerService
   * @param SchemaFactory $schemaFactory
   * @param TranslatorInterface $translator
   */
  public function __construct(EntityManager $entityManager, FormServerApiAdapterService $formServerService, SchemaFactory $schemaFactory, TranslatorInterface $translator)
  {
    $this->em = $entityManager;
    $this->formServerService = $formServerService;
    $this->schemaFactory = $schemaFactory;
    $this->translator = $translator;
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
      $_schema = $this->schemaFactory->createFromArray($schema);
      $documentObject = isset($servizio->getAdditionalData()['document_object']) ? $servizio->getAdditionalData()['document_object'] : Servizio::DEFAULT_DOCUMENT_OBJECT;
      if ($servizio->getServiceGroup() && $servizio->getServiceGroup()->isRegisterInFolder()) {
        # Protocollazione nello stesso fascicolo
        $folderObject = isset($servizio->getServiceGroup()->getAdditionalData()['folder_object']) ? $servizio->getServiceGroup()->getAdditionalData()['folder_object'] : ServiceGroup::DEFAULT_FOLDER_OBJECT;
      } else {
        $folderObject = isset($servizio->getAdditionalData()['folder_object']) ? $servizio->getAdditionalData()['folder_object'] : Servizio::DEFAULT_FOLDER_OBJECT;
      }

      $extraKeys = ["service", "application_id"];
      if ($servizio->getServiceGroup()) {
        $extraKeys[] = "service_group";
      }

      $canEdit = true;

      preg_match_all('/%(.*?)%/', $documentObject, $matches);

      foreach ($matches[1] as $match) {
        if (!$_schema->hasComponent($match) && !in_array($match, $extraKeys)) {
          $event->getForm()->addError(
            new FormError($this->translator->trans('protocollo.invalid_form_key_for_document_object', ['%key%' => $match]))
          );
          $canEdit = false;
        }
      }

      preg_match_all('/%(.*?)%/', $folderObject, $matches);
      foreach ($matches[1] as $match) {
        if (!$_schema->hasComponent($match) && !in_array($match, $extraKeys)) {
          $event->getForm()->addError(
            new FormError($this->translator->trans('protocollo.invalid_form_key_for_folder_object', ['%key%' => $match]))
          );
          $canEdit = false;
        }
      }

      if ($canEdit) {
        $response = $this->formServerService->editForm($schema);
        if ($response['status'] != 'success') {
          $event->getForm()->addError(
            new FormError($response['message'])
          );
        }
      }
    }



    $this->em->persist($servizio);
  }

  public function getBlockPrefix()
  {
    return 'formio_builder_render';
  }

}
