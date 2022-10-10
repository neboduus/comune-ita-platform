<?php


namespace App\Form\Admin\ServiceGroup;

use App\Entity\ServiceGroup;
use App\Helpers\EventTaxonomy;
use App\Model\PublicFile;
use App\Services\FileService\ServiceAttachmentsFileService;
use League\Flysystem\FileExistsException;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\I18n\AbstractI18nType;
use App\Form\I18n\I18nDataMapperInterface;
use App\Form\I18n\I18nTextareaType;
use App\Form\I18n\I18nTextType;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Contracts\Translation\TranslatorInterface;


class ServiceGroupType extends AbstractI18nType
{
  /**
   * @var array
   */
  private $allowedExtensions;

  /**
   * @var ServiceAttachmentsFileService
   */
  private $fileService;

  /**
   * @var TranslatorInterface
   */
  private $translator;

  /**
   * @param I18nDataMapperInterface $dataMapper
   * @param $locale
   * @param $locales
   * @param ServiceAttachmentsFileService $fileService
   * @param TranslatorInterface $translator
   * @param array $allowedExtensions
   */
  public function __construct(
    I18nDataMapperInterface       $dataMapper,
                                  $locale,
                                  $locales,
    ServiceAttachmentsFileService $fileService,
    TranslatorInterface           $translator,
    array                         $allowedExtensions
  )
  {
    parent::__construct($dataMapper, $locale, $locales);
    $this->fileService = $fileService;
    $this->translator = $translator;
    $this->allowedExtensions = array_merge(...$allowedExtensions);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    /** @var ServiceGroup $serviceGroup */
    $serviceGroup = $builder->getData();

    $this->createTranslatableMapper($builder, $options)
      ->add("name", I18nTextType::class, [
        "label" => 'gruppo_di_servizi.nome'
      ])
      ->add("shortDescription", I18nTextareaType::class, [
        "label" => 'servizio.short_description',
        'required' => true,
        'purify_html' => true,
      ])
      ->add("description", I18nTextareaType::class, [
        "label" => 'gruppo_di_servizi.descrizione',
        'required' => false,
        'purify_html' => true,
      ])
      ->add('who', I18nTextareaType::class, [
        'label' => 'gruppo_di_servizi.a_chi_si_rivolge',
        'required' => false,
        'purify_html' => true,
      ])
      ->add('howto', I18nTextareaType::class, [
        'label' => 'gruppo_di_servizi.accedere',
        'required' => false,
        'purify_html' => true,
      ])
      ->add('howToDo', I18nTextareaType::class, [
        'label' => 'servizio.how_to_do',
        'required' => false,
        'purify_html' => true,
      ])
      ->add('whatYouNeed', I18nTextareaType::class, [
        'label' => 'servizio.what_you_need',
        'required' => false,
        'purify_html' => true,
      ])
      ->add('whatYouGet', I18nTextareaType::class, [
        'label' => 'servizio.what_you_get',
        'required' => false,
        'purify_html' => true,
      ])
      ->add('costs', I18nTextareaType::class, [
        'label' => 'servizio.costs',
        'required' => false,
        'purify_html' => true,
      ])
      ->add('specialCases', I18nTextareaType::class, [
        'label' => 'gruppo_di_servizi.casi_particolari',
        'required' => false,
        'purify_html' => true,
      ])
      ->add('moreInfo', I18nTextareaType::class, [
        'label' => 'gruppo_di_servizi.maggiori_info',
        'required' => false,
        'purify_html' => true,
      ])
      ->add("constraints", I18nTextareaType::class, [
        "label" => 'servizio.constraints',
        'required' => false,
        'purify_html' => true,
      ])
      ->add("timesAndDeadlines", I18nTextareaType::class, [
        "label" => 'servizio.times_and_deadlines',
        'required' => false,
        'purify_html' => true,
      ])
      ->add("conditions", I18nTextareaType::class, [
        "label" => 'servizio.conditions',
        'required' => false,
        'purify_html' => true,
      ]);

    $builder
      ->add(
        'conditions_attachments',
        FileType::class,
        [
          'label' => 'servizio.conditions_attachments',
          'help' => 'servizio.attachments_helper',
          'multiple' => true,
          'mapped' => false,
          'required' => false,
          'constraints' => [
            new All([
              new File([
                'maxSize' => '100M',
                'mimeTypes' => $this->allowedExtensions,
                'mimeTypesMessage' => 'Please upload a valid file',
              ])
            ])
          ]
        ]
      )
      ->add(
        'costs_attachments',
        FileType::class,
        [
          'label' => 'servizio.costs_attachments',
          'help' => 'servizio.attachments_helper',
          'multiple' => true,
          'mapped' => false,
          'required' => false,
          'constraints' => [
            new All([
              new File([
                'maxSize' => '100M',
                'mimeTypes' => $this->allowedExtensions,
                'mimeTypesMessage' => 'Please upload a valid file',
              ])
            ])
          ]
        ]
      )
      ->add('sticky', CheckboxType::class, [
        'label' => 'gruppo_di_servizi.in_evidenza',
        'required' => false,
      ])
      ->add(
        'topics',
        EntityType::class,
        [
          'class' => 'App\Entity\Categoria',
          'choice_label' => 'name',
          'label' => 'servizio.categoria',
        ]
      )
      ->add(
        'recipients',
        EntityType::class,
        [
          'class' => 'App\Entity\Recipient',
          'choice_label' => 'name',
          'label' => 'servizio.destinatari',
          'attr' => ['style' => 'columns: 2;'],
          'required' => false,
          'multiple' => true,
          'expanded' => true
        ]
      )
      ->add(
        'geographic_areas',
        EntityType::class,
        [
          'class' => 'App\Entity\GeographicArea',
          'choice_label' => 'name',
          'label' => 'servizio.aree_geografiche',
          'attr' => ['style' => 'columns: 2;'],
          'required' => false,
          'multiple' => true,
          'expanded' => true
        ]
      )
      ->add('coverage', TextType::class, [
        'label' => 'gruppo_di_servizi.copertura_helper',
        'data' => is_array($serviceGroup->getCoverage()) ? implode(',', $serviceGroup->getCoverage()) : $serviceGroup->getCoverage(),
        'required' => false
      ])
      ->add('register_in_folder', CheckboxType::class, [
        'label' => 'gruppo_di_servizi.protocolla_in_fascicolo',
        'required' => false
      ])
      ->add('life_events', ChoiceType::class, [
        'choices' => EventTaxonomy::LIFE_EVENTS,
        'expanded'=> true,
        'multiple' => true,
        'required' => false,
        'attr' => ['style' => 'columns: 3;'],
        'label' => false,
        'empty_data' => []
      ])
      ->add('business_events', ChoiceType::class, [
        'choices' => EventTaxonomy::BUSINESS_EVENTS,
        'expanded'=> true,
        'multiple' => true,
        'required' => false,
        'label' => false,
        'attr' => ['style' => 'columns: 3;'],
        'empty_data' => []
      ]);
    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
  }

  public function onPreSubmit(FormEvent $event)
  {
    /** @var ServiceGroup $service */
    $serviceGroup = $event->getForm()->getData();
    $data = $event->getData();

    foreach ($data['conditions_attachments'] as $attachment) {
      if (!$attachment instanceof UploadedFile) {
        $event->getForm()->addError(new FormError(
          $this->translator->trans('servizio.invalid_file_type')
        ));
      } else {
        try {
          $publicFile = $this->fileService->save($attachment, $serviceGroup, PublicFile::CONDITIONS_TYPE);
          $serviceGroup->addConditionsAttachment($publicFile);
        } catch (FileExistsException $e) {
          $event->getForm()->addError(new FormError(
            $this->translator->trans('servizio.file_already_exists', ['%filename%' => $attachment->getClientOriginalName()])
          ));
        }
      }
    }

    foreach ($event->getData()['costs_attachments'] as $attachment) {
      if (!$attachment instanceof UploadedFile) {
        $event->getForm()->addError(new FormError(
          $this->translator->trans('servizio.invalid_file_type')
        ));
      } else {
        try {
          $publicFile = $this->fileService->save($attachment, $serviceGroup, PublicFile::COSTS_TYPE);
          $serviceGroup->addCostsAttachment($publicFile);
        } catch (FileExistsException $e) {
          $event->getForm()->addError(new FormError(
            $this->translator->trans('servizio.file_already_exists', ['%filename%' => $attachment->getClientOriginalName()])
          ));
        }
      }
    }
  }


  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => ServiceGroup::class,
      'csrf_protection' => false
    ));
    $this->configureTranslationOptions($resolver);
  }

  public function getBlockPrefix()
  {
    return 'app_bundle_service_group_type';
  }
}
