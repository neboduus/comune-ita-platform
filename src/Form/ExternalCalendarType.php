<?php

namespace App\Form;

use App\Model\ExternalCalendar;
use App\Utils\FormUtils;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExternalCalendarType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;
    
    public function __construct(TranslatorInterface $translator)
    {
      $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
      $builder
        ->add('name', TextType::class, [
          'required' => true,
          'label' => 'Nome',
        ])
        ->add('url', UrlType::class, [
          'required' => true,
          'label' => 'Url',
          'help' => 'operatori.create_external_calendar_help'
        ]);
      
      $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
    }

    /**
     * FormEvents::PRE_SUBMIT $listener
     *
     * @param FormEvent $event
     */
    public function onPreSubmit(FormEvent $event)
    {
      $data = $event->getData();
      /** @var ExternalCalendar $externalCalendar */
      $externalCalendar = new ExternalCalendar();
      $externalCalendar->setName($data['name'])->setUrl($data['url']);

      if (!FormUtils::isUrlValid($externalCalendar->getUrl())) {
        $event->getForm()->addError(new FormError(
          $this->translator->trans('operatori.create_calendar_error_url', [
            '%name%' => $externalCalendar->getName()
          ]))
        );
      }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
      $resolver->setDefaults(array(
        'data_class' => 'App\Model\ExternalCalendar',
        'csrf_protection' => false
      ));
    }

    public function getBlockPrefix()
    {
        return 'app_bundle_external_calendar_type';
    }
}
