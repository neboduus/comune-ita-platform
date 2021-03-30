<?php


namespace AppBundle\Form\Admin\Ente;

use AppBundle\Entity\Ente;
use AppBundle\Entity\Pratica;
use AppBundle\Entity\Webhook;
use AppBundle\Services\InstanceService;
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
      'Pratica rifiutata' => Pratica::STATUS_CANCELLED
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

    $builder
      ->add('title', TextType::class, [
        'label' => 'backoffice.entity.title',
        'required' => true
      ])
      ->add('endpoint', UrlType::class, [
        'label' => 'Endpoint',
        'required' => true
      ])
      ->add('method', ChoiceType::class, [
        'label' => 'Method',
        'choices' => $methods,
        'mapped' => false
      ])
      ->add('trigger', ChoiceType::class, [
        'label' => 'Attivatore',
        'choices' => $statuses,
      ])
      ->add('filters', ChoiceType::class, [
        'choices' => $serviceChoices,
        'expanded' => true,
        'multiple' => true,
        'required' => true,
        'label' => 'Seleziona i servizi abilitati per il webhook'
      ])
      ->add('headers', TextareaType::class, [
        'label' => 'Headers (json)',
        'required' => false
      ])
      ->add('active', CheckboxType::class, [
        'label' =>  'Attivo?' ,
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
