<?php


namespace App\Form\Admin\Servizio;


use App\Entity\Servizio;
use App\Protocollo\ExternalProtocolloHandler;
use App\Protocollo\ProvidersCollection;
use App\Services\ExternalProtocolService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Contracts\Translation\TranslatorInterface;


class ProtocolDataType extends AbstractType
{
  /** @var ProvidersCollection */
  private ProvidersCollection $providersCollection;

  /** @var ExternalProtocolService */
  private ExternalProtocolService $externalProtocolService;

  /** @var TranslatorInterface */
  private TranslatorInterface $translator;

  /**
   * @param ExternalProtocolService $externalProtocolService
   * @param ProvidersCollection $providersCollection
   * @param TranslatorInterface $translator
   */
  public function __construct(
    ExternalProtocolService $externalProtocolService,
    ProvidersCollection $providersCollection,
    TranslatorInterface $translator
  )
  {
    $this->externalProtocolService = $externalProtocolService;
    $this->providersCollection = $providersCollection;
    $this->translator = $translator;
  }

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    /** @var Servizio $service */
    $service = $builder->getData();
    $currentServiceParameters = $service->getProtocolloParameters();

    $availableRegisterProviders = $this->providersCollection->getAvailableRegisterProviders();
    $handlerList = [];
    foreach ($availableRegisterProviders as $alias => $provider){
      $handlerList[$provider['name']] = $alias;
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
        'choice_attr' => function($choice, $key, $value) use ($availableRegisterProviders, $service) {
          $handler = $availableRegisterProviders[$choice]['handler'];
          $isExternalProtocolHandler = $handler instanceof ExternalProtocolloHandler;
          $attr = [];
          if ($isExternalProtocolHandler) {
            $attr['class'] = 'external-register-choice';
            $attr['data-tenant'] = $service->getEnte()->getId();
            $attr['data-service'] = $service->getId();
            $attr['data-identifier'] = $choice;
            if (isset($availableRegisterProviders[$choice]['url'])) {
              $attr['data-url'] = $availableRegisterProviders[$choice]['url'];
            }
            if (isset($availableRegisterProviders[$choice]['headers'])) {
              $attr['data-headers'] = implode(',', $availableRegisterProviders[$choice]['headers'] ?? []);
            }
          }
          return $attr;
        },
        'expanded' => true,
        'required' => false
      ]);

    foreach ($this->providersCollection->getHandlers() as $alias => $handler) {
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
                    'data' => isset($currentServiceParameters[$key]) && boolval($currentServiceParameters[$key]),
                    'mapped' => false,
                    'required' => false,
                    'attr' => $attr
                  ]);
                break;
              default:
                $builder
                  ->add($key, TextType::class, [
                      'label' => 'protocollo.' . $key,
                      'data' => $currentServiceParameters[$key] ?? '',
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
                    'data' => $currentServiceParameters[$key][$subparam] ?? '',
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
                'data' => $currentServiceParameters[$param] ?? '',
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
        new FormError($this->translator->trans('servizio.protocol_provider_required'))
      );
    }

    $availableProviders = $this->providersCollection->getAvailableRegisterProviders();

    if (isset($data['protocol_handler']) && !empty($data['protocol_handler'])) {
      $providerKey = $data['protocol_handler'];

      if (!key_exists($providerKey, $availableProviders)) {
        $event->getForm()->addError(
          new FormError($this->translator->trans('servizio.protocol_provider_not_exists'))
        );
      } else if ($availableProviders[$providerKey]['handler']->getIdentifier() === ExternalProtocolloHandler::IDENTIFIER) {
        $config = $availableProviders[$providerKey];
        try {
          $tenant = $this->externalProtocolService->setup($config);
        } catch (\Exception $e) {
          // Reset protocol data
          $data['protocol_handler'] = $service->getProtocolHandler();
          $data['protocol_required'] = $service->getPaymentRequired();;
          $event->setData($data);

          $event->getForm()->addError(
            new FormError($e->getMessage())
          );
        }
      }
    }
    $service->setProtocolloParameters($data);
  }




  public function getBlockPrefix()
  {
    return 'protocol_data';
  }
}
