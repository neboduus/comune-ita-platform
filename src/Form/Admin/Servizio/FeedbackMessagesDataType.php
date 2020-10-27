<?php


namespace App\Form\Admin\Servizio;


use App\Entity\Pratica;
use App\Entity\Servizio;
use App\Form\FeedbackMessageType;
use App\Model\FeedbackMessage;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

class FeedbackMessagesDataType extends AbstractType
{

  /**
   * @var TranslatorInterface $translator
   */
  private $translator;

  /**
   * @var EntityManager
   */
  private $em;

  /**
   * FeedbackMessagesDataType constructor.
   */
  public function __construct(TranslatorInterface $translator, EntityManagerInterface $entityManager)
  {
    $this->translator = $translator;
    $this->em = $entityManager;
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
  }

  public function getBlockPrefix()
  {
    return 'feedback_messages_data';
  }
}
