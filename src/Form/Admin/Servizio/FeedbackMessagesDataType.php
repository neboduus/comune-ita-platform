<?php


namespace App\Form\Admin\Servizio;


use App\Entity\Servizio;
use App\Form\FeedbackMessageType;
use App\FormIO\SchemaFactoryInterface;
use App\Model\FeedbackMessage;
use App\Model\FeedbackMessagesSettings;
use App\Model\Mailer;
use App\Services\Manager\ServiceManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FeedbackMessagesDataType extends AbstractType
{

  /**
   * @var ServiceManager
   */
  private $serviceManager;

  /**
   * @var EntityManager
   */
  private $entityManager;

  /** @var SchemaFactoryInterface */
  private $schemaFactory;

  private $locales = [];
  private $defaultLocale;

  /**
   * FeedbackMessagesDataType constructor.
   * @param ServiceManager $serviceManager
   * @param EntityManagerInterface $entityManager
   * @param SchemaFactoryInterface $schemaFactory
   * @param $locales
   * @param $defaultLocale
   */
  public function __construct(ServiceManager $serviceManager, EntityManagerInterface $entityManager, SchemaFactoryInterface $schemaFactory, $locales, $defaultLocale)
  {
    $this->serviceManager = $serviceManager;
    $this->entityManager = $entityManager;
    $this->schemaFactory = $schemaFactory;
    $this->locales = explode('|', $locales);
    $this->defaultLocale = $defaultLocale;
  }

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
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
      foreach ($schema->getComponents() as $component) {
        if ($component->getType() == 'text' || $component->getType() == 'email') {
          $components[$component->getLabel()] = $component->getName();
        }
      }
    }

    $translationsRepo = $this->entityManager->getRepository('Gedmo\Translatable\Entity\Translation');
    $translations = $translationsRepo->findTranslations($service);

    // Fix per traduzione vecchi oggetti
    if (empty($translations[$this->defaultLocale]['feedbackMessages']) && !empty($service->getFeedbackMessages())) {
      $translations[$this->defaultLocale]['feedbackMessages'] = json_encode($service->getFeedbackMessages());
    }

    $i18nMessages = [];
    $defaultMessages = $this->serviceManager->getDefaultFeedbackMessages();

    foreach ($this->locales as $locale) {
      $savedFeedbackMessages = isset($translations[$locale]['feedbackMessages']) ? \json_decode($translations[$locale]['feedbackMessages'], 1) : [];

      $feedbackMessagesStatuses = array_keys(FeedbackMessage::STATUS_NAMES);
      foreach ($feedbackMessagesStatuses as $status) {
        /** @var FeedbackMessage $feedbackMessage */
        $feedbackMessage = $defaultMessages[$locale][$status];
        $savedMessage = $savedFeedbackMessages[$status] ?? null;

        if ($savedMessage) {
          // Overwrite default message with saved locale feedback message
          if (isset($savedMessage['subject']) && $savedMessage['subject']) {
            $feedbackMessage->setSubject($savedMessage['subject']);
          }
          if (isset($savedMessage['message']) && $savedMessage['message']) {
            $feedbackMessage->setMessage($savedMessage['message']);
          }
          if (isset($savedMessage['is_active'])) {
            $feedbackMessage->setIsActive($savedMessage['is_active']);
          }
          if (isset($savedMessage['isActive'])) {
            $feedbackMessage->setIsActive($savedMessage['isActive']);
          }
        }

        $i18nMessages[$locale][]= $feedbackMessage;
      }
    }

    if (!empty($mailers)) {
      $builder
        ->add(FeedbackMessagesSettings::KEY, FeedbackMessagesSettingsType::class, [
          'label' => false,
          'mapped' => false,
          'data' => $service->getFeedbackMessagesSettings(),
          'mailers' => $mailers,
          'components' => $components ?? [],
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

  /**
   * @throws OptimisticLockException
   * @throws ORMException
   */
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
        if (!isset($feedbackMessage['isActive']) && !isset($feedbackMessage['is_active'])) {
          $feedbackMessage['isActive'] = '0';
        }
        $messages[$feedbackMessage['trigger']] = $feedbackMessage;
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
