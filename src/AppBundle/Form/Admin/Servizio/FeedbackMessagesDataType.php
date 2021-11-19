<?php


namespace AppBundle\Form\Admin\Servizio;


use AppBundle\Entity\Ente;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Servizio;
use AppBundle\Form\FeedbackMessageType;
use AppBundle\FormIO\SchemaComponent;
use AppBundle\FormIO\SchemaFactory;
use AppBundle\Model\FeedbackMessage;
use AppBundle\Model\FeedbackMessagesSettings;
use AppBundle\Model\Mailer;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class FeedbackMessagesDataType extends AbstractType
{

  /**
   * @var TranslatorInterface $translator
   */
  private $translator;

  /**
   * @var EntityManager
   */
  private $entityManager;

  /** @var SchemaFactory */
  private $schemaFactory;

  private $locales = [];

  /**
   * FeedbackMessagesDataType constructor.
   * @param TranslatorInterface $translator
   * @param EntityManagerInterface $entityManager
   * @param SchemaFactory $schemaFactory
   * @param $locales
   */
  public function __construct(TranslatorInterface $translator, EntityManagerInterface $entityManager, SchemaFactory $schemaFactory, $locales)
  {
    $this->translator = $translator;
    $this->entityManager = $entityManager;
    $this->schemaFactory = $schemaFactory;
    $this->locales = explode('|', $locales);
  }

  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    $status = [
      Pratica::STATUS_PRE_SUBMIT => 'Inviata',
      Pratica::STATUS_SUBMITTED => 'Acquisita',
      Pratica::STATUS_REGISTERED => 'Protocollata',
      Pratica::STATUS_PENDING => 'Presa in carico',
      Pratica::STATUS_COMPLETE => 'Iter completato',
      Pratica::STATUS_CANCELLED => 'Rifiutata',
      Pratica::STATUS_WITHDRAW => 'Ritirata',
      Pratica::STATUS_DRAFT => 'Bozza',
    ];

    /** @var Servizio $service */
    $service = $builder->getData();

    $mailers = [];
    if (!empty($service->getEnte()->getMailers())) {
      // Ricavo i mailer disponibili dall'istanza
      $mailers = [
        'Default del sistema' => 'disabled',
      ];
      /** @var Mailer $mailer */
      foreach ($service->getEnte()->getMailers() as $mailer) {
        $mailers[$mailer->getTitle()] = $mailer->getIdentifier();
      }

      // Recupero lo schema del servizio
      $components = [];
      $schema = $this->schemaFactory->createFromFormId($service->getFormIoId());
      /** @var SchemaComponent $component */
      foreach ($schema->getComponents() as $component) {
        if ($component->getType() == 'text' || $component->getType() == 'email') {
          $components[$component->getLabel()] = $component->getName();
        }
      }
    }

    $translationsRepo = $this->entityManager->getRepository('Gedmo\Translatable\Entity\Translation');
    $translations = $translationsRepo->findTranslations($service);

    $i18nMessages = [];
    foreach ($this->locales as $locale) {

      $savedFeedbackMessages = isset($translations[$locale]['feedbackMessages']) ? \json_decode($translations[$locale]['feedbackMessages'], 1) : [];
      foreach ($status as $k => $v) {
        $tempMessage = isset($savedFeedbackMessages[$k]) ? $savedFeedbackMessages[$k] : null;
        $temp = new FeedbackMessage();
        $temp->setName($v);
        $temp->setTrigger($k);
        $temp->setSubject(
          $tempMessage['subject'] ?? $this->translator->trans('pratica.email.status_change.subject', [], null, $locale
          )
        );
        $temp->setMessage(
          $tempMessage['message'] ?? $this->translator->trans('messages.pratica.status.'.$k, [], null, $locale)
        );

        $defaultIsActive = true;
        if ($k == Pratica::STATUS_PENDING || $k == Pratica::STATUS_DRAFT) {
          $defaultIsActive = false;
        }
        $temp->setIsActive(
          $tempMessage['isActive'] ?? $defaultIsActive
        );
        $i18nMessages[$locale][]= $temp;
      }
    }

    if (!empty($mailers)) {
      $builder
        ->add(FeedbackMessagesSettings::KEY, FeedbackMessagesSettingsType::class, [
          'label' => false,
          'mapped' => false,
          'data' => $service->getFeedbackMessagesSettings(),
          'mailers' => $mailers,
          'components' => $components,
        ]);
    }

    $i18nForm = $builder->create('i18n', FormType::class, [
        'mapped' => false
      ]
    );

    foreach ($this->locales as $locale) {
      $localeForm = $builder->create($locale, FormType::class, [
          'mapped' => false,
        ]
      );
      $localeForm
        ->add(
          'feedback_messages',
          CollectionType::class,
          [
            'required' => false,
            'data' => $i18nMessages[$locale],
            'label' => false,
            'entry_type' => FeedbackMessageType::class,
            'entry_options' => [
              'label' => false,
            ],
            'allow_add' => false,
            'allow_delete' => false,
          ]
        );
      $i18nForm->add($localeForm);
    }

    $builder->add($i18nForm);

    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
  }

  public function onPreSubmit(FormEvent $event)
  {
    /** @var Servizio $service */
    $service = $event->getForm()->getData();
    $data = $event->getData();

    if (isset($data[FeedbackMessagesSettings::KEY])) {
      $service->setFeedbackMessagesSettings($data[FeedbackMessagesSettings::KEY]);
    }
    $this->entityManager->persist($service);

    $repository = $this->entityManager->getRepository('Gedmo\\Translatable\\Entity\\Translation');
    foreach ($data['i18n'] as $k => $v) {
      $messages = [];
      foreach ($v['feedback_messages'] as $feedbackMessage) {
        if (!isset($feedbackMessage['isActive'])) {
          $feedbackMessage['isActive'] = '0';
        }
        $messages [$feedbackMessage['trigger']] = $feedbackMessage;
      }
      $repository->translate($service, 'feedbackMessages', $k, $messages);
      $this->entityManager->persist($service);
    }

    $this->entityManager->flush();
  }

  /**
   * {@inheritdoc}
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'allow_extra_fields' => true,
    ));
  }

  public function getBlockPrefix()
  {
    return 'feedback_messages_data';
  }
}
