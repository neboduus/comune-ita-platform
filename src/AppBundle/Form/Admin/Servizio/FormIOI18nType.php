<?php

namespace AppBundle\Form\Admin\Servizio;

use AppBundle\Entity\FormIO;
use AppBundle\Entity\SciaPraticaEdilizia;
use AppBundle\Entity\Servizio;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use AppBundle\Form\FeedbackMessageType;
use AppBundle\Services\FormServerApiAdapterService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Form\FormError;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\GuzzleException;


class FormIOI18nType extends AbstractType
{
  /**
   * @var EntityManager
   */
  private $em;

  /**
   * @var FormServerApiAdapterService
   */
  private $formServerService;

  private $locale;

  private $locales = [];

  private $labels = [];

  private $translated = [];

  /**
   * @param EntityManagerInterface $entityManager
   * @param FormServerApiAdapterService $formServerService
   * @param $locale
   * @param $locales
   */
  public function __construct(EntityManagerInterface $entityManager, FormServerApiAdapterService $formServerService, $locale, $locales)
  {
    $this->em = $entityManager;
    $this->formServerService = $formServerService;
    $this->locale = $locale;
    $this->locales = explode('|', $locales);
    if (($key = array_search($locale, $this->locales)) !== false) {
      unset($this->locales[$key]);
    }
  }

  /**
   * @param FormBuilderInterface $builder
   * @param array $options
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    /** @var Servizio $servizio */
    $servizio = $builder->getData();
    $formId = $servizio->getFormIoId();

    $labels = $this->formServerService->getI18nLabels($formId);
    $translated = $this->formServerService->getTranslations($formId);

    $i18nForm = $builder->create('i18n', FormType::class, ['mapped' => false]);
    if ($labels['status'] == 'success' && $translated['status'] == 'success') {

      foreach ($labels['data'] as $label) {
        $this->labels[md5($label)] = $label;
      }
      $this->translated = $translated['data'];

      foreach ($this->locales as $locale) {
        $localeForm = $builder->create($locale, FormType::class, [
            'mapped' => false,
          ]
        );

        foreach ($this->labels as $k => $label) {
          $localeForm
            ->add(
              $k,
              TextType::class,
              [
                'required' => false,
                'data' => $this->translated[$locale][$label] ?? '',
                'attr' => ['class' => 'bg-white border-top border-left border-right border-100 form-control-sm'],
                'label' => $label,
              ]
            );
        }
        $i18nForm->add($localeForm);
      }
    }
    $builder->add($i18nForm);
    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
  }

  public function onPreSubmit(FormEvent $event)
  {
    /** @var Servizio $servizio */
    $servizio = $event->getForm()->getData();
    $formId = $servizio->getFormIoId();

    $data = $event->getData();
    $errors = false;
    foreach ($data['i18n'] as $locale => $fields) {
      $translations = [];
      foreach ($fields as $k => $v) {
        $translations[$locale][(string)$this->labels[$k]] = $v;
      }
      $update = isset($this->translated[$locale]) && !empty($this->translated[$locale]);
      $result = $this->formServerService->saveTranslations($formId, $translations, $update);
      if ($result['status'] != 'success') {
        $errors = true;
      }
    }

    if ($errors) {
      $event->getForm()->addError(
        new FormError('Si è verificato un problema nel salvataggio delle lingue.')
      );
    }
  }

  public function getBlockPrefix()
  {
    return 'formio_i18n';
  }

}
