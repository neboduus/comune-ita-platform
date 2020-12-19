<?php

namespace App\Form\Admin\Servizio;

use App\Entity\Pratica;
use App\Entity\Servizio;
use App\Form\FeedbackMessageType;
use App\Model\FeedbackMessage;
use App\Entity\Ente;
use App\FormIO\SchemaComponent;
use App\FormIO\SchemaFactory;
use App\Model\FeedbackMessagesSettings;
use App\Model\Mailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FeedbackMessagesDataType extends AbstractType
{

  /**
   * @var TranslatorInterface $translator
   */
  private $translator;

  /**
   * @var EntityManagerInterface
   */
  private $entityManager;

  /** @var SchemaFactory */
  private $schemaFactory;

  /**
   * FeedbackMessagesDataType constructor.
   * @param TranslatorInterface $translator
   * @param EntityManagerInterface $entityManager
   * @param SchemaFactory $schemaFactory
   */
  public function __construct(TranslatorInterface $translator, EntityManagerInterface $entityManager, SchemaFactory $schemaFactory)
  {
    $this->translator = $translator;
    $this->entityManager = $entityManager;
    $this->schemaFactory = $schemaFactory;
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
    ];

    /** @var Servizio $service */
    $service = $builder->getData();

    $mailers = [];
    if (!empty($service->getEnte()->getMailers())) {
      // Ricavo i mailer disponibili dall'istanza
      $mailers = [
        'Disabilitato' => 'disabled',
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
        if ($component->getType() == 'text') {
          $components[$component->getLabel()] = $component->getName();
        }
      }
    }

    // Recupero i messaggi inviati al cambio di stato
    $savedMessages = $service->getFeedbackMessages();
    $messages = [];
    foreach ($status as $k => $v) {
      $tempMessage = isset($savedMessages[$k]) ? (array)$savedMessages[$k] : null;

      $temp = new FeedbackMessage();
      $temp->setName($v);
      $temp->setTrigger($k);
      $temp->setMessage(
        isset($tempMessage['message']) ? $tempMessage['message'] : $this->translator->trans('messages.pratica.status.'.$k)
      );

      $defaultIsActive = true;
      if ($k == Pratica::STATUS_PENDING) {
        $defaultIsActive = false;
      }
      $temp->setIsActive(
        isset($tempMessage['isActive']) ? $tempMessage['isActive'] : $defaultIsActive
      );
      $messages[] = $temp;
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

    $builder
      ->add(
        'feedback_messages',
        CollectionType::class,
        [
          'required' => false,
          'data' => $messages,
          'label' => false,
          'entry_type' => FeedbackMessageType::class,
          'entry_options' => [
            'label' => false,
          ],
          'allow_add' => false,
          'allow_delete' => false,
        ]
      );

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
