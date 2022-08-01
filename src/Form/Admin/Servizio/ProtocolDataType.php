<?php


namespace App\Form\Admin\Servizio;


use App\Entity\Servizio;
use App\Protocollo\ProtocolloHandlerRegistry;
use App\Services\ProtocolloService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;


class ProtocolDataType extends AbstractType
{
  /**
   * @var ProtocolloService
   */
  private $protocolloService;

  /**
   * @var EntityManager
   */
  private $em;

  /**
   * @var ProtocolloHandlerRegistry
   */
  private $handlerRegistry;

  public function __construct(ProtocolloService $protocolloService, EntityManagerInterface $entityManager, ProtocolloHandlerRegistry $handlerRegistry)
  {
    $this->protocolloService = $protocolloService;
    $this->em = $entityManager;
    $this->handlerRegistry = $handlerRegistry;
  }

  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    /** @var Servizio $service */
    $service = $builder->getData();
    $currentServiceParameters = $service->getProtocolloParameters();

    $handlerList = [];
    foreach ($this->handlerRegistry->getAvailableHandlers() as $alias => $handler){
      $handlerList[$handler->getName()] = $alias;
    }

    $builder
      ->add('protocol_required', CheckboxType::class, [
        'label' => 'nav.backoffices.request_protocol',
        'required' => false
      ]);

    $builder
      ->add('protocol_handler', ChoiceType::class, [
        'label' => 'nav.backoffices.protocol_type',
        'choices' => $handlerList,
        'required' => false
      ]);

    foreach ($this->handlerRegistry->getAvailableHandlers() as $alias => $handler){
      $this->buildConfigParameters($builder, $service, $alias, $handler->getConfigParameters(), $currentServiceParameters);
    }

    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
  }

  private function buildConfigParameters($builder, $service, $alias, $configParameters, $currentServiceParameters)
  {
    $attr = ['class' => 'protocollo_params ' . $alias];
    if (!$service->isProtocolRequired() || $service->getProtocolHandler() != $alias ) {
      $attr['disabled'] = 'disabled';
    }

    if ($configParameters) {

      foreach ($configParameters as $key => $param) {
        if (is_array($param)) {
          // First step to migration
          if (isset($param['type'])) {
            switch ($param['type']) {
              case 'bool':
                $builder
                  ->add($key, CheckboxType::class, [
                    'label' => 'protocollo.' . $key,
                    'data' => isset($currentServiceParameters[$key]) ? boolval($currentServiceParameters[$key]) : false,
                    'mapped' => false,
                    'required' => false,
                    'attr' => $attr
                  ]);
                break;
              default:
                $builder
                  ->add($key, TextType::class, [
                      'label' => 'protocollo.' . $key,
                      'data' => isset($currentServiceParameters[$key]) ? $currentServiceParameters[$key] : '',
                      'mapped' => false,
                      'required' => $param['required'] ?? true,
                      'attr' => $attr
                    ]
                  );
                break;
            }
          } else {
            $paramForm = $builder->create( $key, FormType::class, [
              'mapped' => false,
              'label' => false
            ]);

            foreach ($param as $subparam) {
              $paramForm
                ->add($subparam, TextType::class, [
                    'label' => 'protocollo.' . $key . '.' . $subparam,
                    'data' => isset($currentServiceParameters[$key][$subparam]) ? $currentServiceParameters[$key][$subparam] : '',
                    'mapped' => false,
                    'required' => true,
                    'attr' => $attr
                  ]
                );
            }
            $builder->add($paramForm);
          }

        } else {
          $builder
            ->add($param, TextType::class, [
                'label' => 'protocollo.' . $param,
                'data' => isset($currentServiceParameters[$param]) ? $currentServiceParameters[$param] : '',
                'mapped' => false,
                'required' => true,
                'attr' => $attr
              ]
            );
        }
      }
    }
  }

  public function onPreSubmit(FormEvent $event)
  {
    /** @var Servizio $service */
    $service = $event->getForm()->getData();
    $data = $event->getData();


    if (isset($data['protocol_required']) && !empty($data['protocol_required']) && empty($data['protocol_handler'])) {
      $event->getForm()->addError(
        new FormError('Devi selezionare almeno un tipo di protocollo per abilitare la protocollazione!')
      );
    }

    $service->setProtocolloParameters($data);
    $this->em->persist($service);
  }


  public function getBlockPrefix()
  {
    return 'protocol_data';
  }
}
