<?php


namespace AppBundle\Form\Admin\Servizio;


use AppBundle\Entity\Servizio;
use AppBundle\Form\Base\BlockQuoteType;
use AppBundle\Form\PaymentParametersType;
use AppBundle\Protocollo\PiTreProtocolloParameters;
use AppBundle\Services\ProtocolloService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;


class ProtocolDataType extends AbstractType
{
  /**
   * @var Container
   */
  private $protocolloService;

  /**
   * @var EntityManager
   */
  private $em;

  public function __construct(ProtocolloService $protocolloService, EntityManagerInterface $entityManager)
  {
    $this->protocolloService = $protocolloService;
    $this->em = $entityManager;
  }

  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    /** @var Servizio $service */
    $service = $builder->getData();
    $configParameters = $this->protocolloService->getHandler()->getConfigParameters();

    $currentServiceParameters = $service->getProtocolloParameters();

    $builder
      ->add('protocol_required', CheckboxType::class, [
        'label' => 'Protocollazione richiesta?',
        'required' => false
      ]);
    if ($configParameters) {
      $builder
        ->add('parameters_needed', BlockQuoteType::class, [
          'label' => 'Inserisci qui i parametri di configurazione del protocollo'
        ]);
      $attr = $service->isProtocolRequired() ? ['class' => 'protocollo_params'] : ['disabled' => 'disabled', 'class' => 'protocollo_params'];
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
                      'required' => true,
                      'attr' => $attr
                    ]
                  );
                break;
            }
          } else {
            $paramForm = $builder->create( $key,FormType::class, [
              'mapped' => false,
              'label_attr' => ['class' => 'pb-4'],
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
    } else {
      $builder
        ->add('no_parameters_needed', BlockQuoteType::class, [
          'label' => 'Il protocollo attuale non prevede ulteriori parametri di configurazione'
        ]);
    }

    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
  }

  public function onPreSubmit(FormEvent $event)
  {
    /** @var Servizio $service */
    $service = $event->getForm()->getData();
    $data = $event->getData();
    /*$configParameters = $this->protocolloService->getHandler()->getConfigParameters();
    $parameters = [];

    if ($configParameters) {
      foreach ($configParameters as $param) {
        if (!isset($data[$param]) || empty($data[$param])) {
          $event->getForm()->addError(
            new FormError('Tutti i parametri sono obbligatori')
          );
        }
        $parameters[$param] = $data[$param];
      }
    }*/

    $service->setProtocolloParameters($data);
    $this->em->persist($service);
  }


  public function getBlockPrefix()
  {
    return 'protocol_data';
  }
}
