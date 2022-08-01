<?php


namespace App\Form\Admin\Ente;

use App\Entity\Ente;
use App\Entity\Pratica;
use App\Entity\Webhook;
use App\Services\InstanceService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WebhookType extends AbstractType
{

  /**
   * @var EntityManager
   */
  private $entityManager;

  /**
   * @var InstanceService
   */
  private $instanceService;

  public function __construct(EntityManagerInterface $entityManager, InstanceService $instanceService)
  {
    $this->entityManager = $entityManager;
    $this->instanceService = $instanceService;
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    $statuses = [
      'Tutti' => 'all',
      'Pratica pagata' => Pratica::STATUS_PAYMENT_SUCCESS,
      'Pratica inviata' => Pratica::STATUS_PRE_SUBMIT,
      'Pratica acquisita' => Pratica::STATUS_SUBMITTED,
      'Pratica protocollata' => Pratica::STATUS_REGISTERED,
      'Pratica presa in carico' => Pratica::STATUS_PENDING,
      'Pratica accettata' => Pratica::STATUS_COMPLETE,
      'Pratica rifiutata' => Pratica::STATUS_CANCELLED,
      'Messaggio creato' => Webhook::TRIGGER_MESSAGE_CREATED,
    ];

    $methods = [
      'POST' => 'POST'
    ];

    $servizi = $this->instanceService->getServices();
    $serviceChoices = [];
    $serviceChoices['Tutti'] = 'all';
    foreach ($servizi as $s) {
      $serviceChoices[$s->getName()] = $s->getId();
    }

    /** @var Webhook $webHook */
    $webHook = $builder->getData();
    if ($webHook->getVersion() === null) {
      $versions = Webhook::VERSIONS;
      $webHook->setVersion(end($versions));
    }

    $builder
      ->add('title', TextType::class, [
        'label' => 'webhook.nome',
        'required' => true
      ])
      ->add('endpoint', UrlType::class, [
        'label' => 'webhook.endpoint',
        'required' => true
      ])
      ->add('method', ChoiceType::class, [
        'label' => 'webhook.method',
        'choices' => $methods,
        'mapped' => false
      ])
      ->add('trigger', ChoiceType::class, [
        'label' => 'webhook.trigger',
        'choices' => $statuses,
      ])
      ->add('filters', ChoiceType::class, [
        'choices' => $serviceChoices,
        'expanded' => true,
        'multiple' => true,
        'required' => true,
        'label' => 'webhook.filters_label'
      ])
      ->add('headers', TextareaType::class, [
        'label' => 'webhook.headers',
        'required' => false
      ])
      ->add('version', ChoiceType::class, [
        'label' => 'webhook.version',
        'choices' => Webhook::VERSIONS,
      ])
      ->add('active', CheckboxType::class, [
        'label' =>  'webhook.active' ,
        'required' => false
      ])
    ;
  }

  /**
   * {@inheritdoc}
   */
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults(array(
      'data_class' => Webhook::class
    ));
  }
}
