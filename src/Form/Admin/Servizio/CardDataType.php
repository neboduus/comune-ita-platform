<?php


namespace App\Form\Admin\Servizio;


use App\Entity\Servizio;
use App\Form\I18n\AbstractI18nType;
use App\Form\I18n\I18nDataMapperInterface;
use App\Form\I18n\I18nTextareaType;
use App\Helpers\EventTaxonomy;
use App\Model\PublicFile;
use App\Services\FileService\ServiceAttachmentsFileService;
use League\Flysystem\FileExistsException;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Contracts\Translation\TranslatorInterface;

class CardDataType extends AbstractI18nType
{

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

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    /** @var Servizio $servizio */
    $servizio = $builder->getData();

    // you can add the translatable fields
    $this->createTranslatableMapper($builder, $options)
      ->add("shortDescription", I18nTextareaType::class, [
        "label" => 'servizio.short_description',
        'required' => true,
        'attr' => ['class' => 'simple'],
        'purify_html' => true,
      ])
      ->add("description", I18nTextareaType::class, [
        "label" => 'servizio.cos_e',
        'purify_html' => true,
      ])
      ->add("who", I18nTextareaType::class, [
        "label" => 'servizio.a_chi_si_rivolge',
        'purify_html' => true,
      ])
      ->add("howto", I18nTextareaType::class, [
        "label" => 'servizio.accedere',
        'purify_html' => true,
      ])
      ->add("howToDo", I18nTextareaType::class, [
        "label" => 'servizio.how_to_do',
        'purify_html' => true,
      ])
      ->add("whatYouNeed", I18nTextareaType::class, [
        "label" => 'servizio.what_you_need',
        'purify_html' => true,
      ])
      ->add("whatYouGet", I18nTextareaType::class, [
        "label" => 'servizio.what_you_get',
        'purify_html' => true,
      ])
      ->add("costs", I18nTextareaType::class, [
        "label" => 'servizio.costs',
        'purify_html' => true,
      ])
      ->add("specialCases", I18nTextareaType::class, [
        "label" => 'servizio.casi_particolari',
        'purify_html' => true,
      ])
      ->add("moreInfo", I18nTextareaType::class, [
        "label" => 'servizio.maggiori_info',
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
      ->add('bookingCallToAction', UrlType::class, [
        "label" => 'servizio.booking_call_to_action',
        'required' => false,
        'help' => 'servizio.booking_cta_help'
      ])
      ->add(
        'conditionsAttachments',
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
        'costsAttachments',
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
        'geographicAreas',
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
      ->add(
        'coverage',
        TextType::class,
        [
          'label' => 'servizio.copertura_helper',
          'data' => is_array($servizio->getCoverage()) ? implode(
            ',',
            $servizio->getCoverage()
          ) : $servizio->getCoverage(),
          'required' => false,
        ]
      )
      ->add('life_events', ChoiceType::class, [
        'choices' => EventTaxonomy::LIFE_EVENTS,
        'expanded' => true,
        'multiple' => true,
        'required' => false,
        'attr' => ['style' => 'columns: 3;'],
        'label' => false,
      ])
      ->add('business_events', ChoiceType::class, [
        'choices' => EventTaxonomy::BUSINESS_EVENTS,
        'expanded' => true,
        'multiple' => true,
        'required' => false,
        'label' => false,
        'attr' => ['style' => 'columns: 3;'],
      ]);
    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
  }

  public function onPreSubmit(FormEvent $event)
  {
    /** @var Servizio $service */
    $service = $event->getForm()->getData();
    $data = $event->getData();

    if ($data['shortDescription'][$this->getLocale()] === $service->getName())
    {
      $event->getForm()->addError(new FormError($this->translator->trans("servizio.change_short_description")));
    }


    foreach ($data['conditionsAttachments'] as $attachment) {
      if (!$attachment instanceof UploadedFile) {
        $event->getForm()->addError(new FormError(
          $this->translator->trans('servizio.invalid_file_type')
        ));
      } else {
        try {
          $publicFile = $this->fileService->save($attachment, $service, PublicFile::CONDITIONS_TYPE);
          $service->addConditionsAttachment($publicFile);
        } catch (FileExistsException $e) {
          $event->getForm()->addError(new FormError(
            $this->translator->trans('servizio.file_already_exists', ['%filename%' => $attachment->getClientOriginalName()])
          ));
        }
      }
    }

    foreach ($data['costsAttachments'] as $attachment) {
      if (!$attachment instanceof UploadedFile) {
        $event->getForm()->addError(new FormError(
          $this->translator->trans('servizio.invalid_file_type')
        ));
      } else {
        try {
          $publicFile = $this->fileService->save($attachment, $service, PublicFile::COSTS_TYPE);
          $service->addCostsAttachment($publicFile);
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
      'data_class' => 'App\Entity\Servizio'
    ));

    $this->configureTranslationOptions($resolver);
  }

  public function getBlockPrefix()
  {
    return 'card_data';
  }
}
