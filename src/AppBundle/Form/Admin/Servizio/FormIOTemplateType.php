<?php

namespace AppBundle\Form\Admin\Servizio;

use AppBundle\Entity\FormIO;
use AppBundle\Entity\SciaPraticaEdilizia;
use AppBundle\Entity\Servizio;
use AppBundle\Form\Extension\TestiAccompagnatoriProcedura;
use AppBundle\Services\FormServerApiAdapterService;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Form\FormError;


class FormIOTemplateType extends AbstractType
{
  /**
   * @var EntityManager
   */
  private $em;

  /**
   * ChooseAllegatoType constructor.
   *
   * @param EntityManager $entityManager
   */
  public function __construct(EntityManager $entityManager)
  {
    $this->em = $entityManager;
  }

  /**
   * @param FormBuilderInterface $builder
   * @param array $options
   */
  public function buildForm(FormBuilderInterface $builder, array $options)
  {

    /** @var Servizio $servizio */
    $servizio = $builder->getData();
    $additionalData = $servizio->getAdditionalData();

    $formId = isset($additionalData['formio_id']) && !empty($additionalData['formio_id']) ? $additionalData['formio_id'] : '';
    $builder
      ->add('form_id', HiddenType::class,
        [
          'attr' => ['value' => $formId],
          'mapped' => false,
          'required' => false,
        ]
      );
    $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'onPreSubmit'));
    //$builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'onPostSubmit'));
  }


  public function getBlockPrefix()
  {
    return 'formio_template';
  }

  public function onPreSubmit(FormEvent $event)
  {
    /** @var Servizio $servizio */
    $servizio = $event->getForm()->getData();

    if (isset($event->getData()['form_id']) && !empty($event->getData()['form_id'])) {

      $formId = $event->getData()['form_id'];

      if ( $formId == 'new' ) {
        $response = FormServerApiAdapterService::createService($servizio);

        if ($response['status'] == 'success') {
          $formId = $response['form_id'];
        } else {
          $event->getForm()->addError(
            new FormError($response['message'])
          );
        }
      }

      $additionalData = $servizio->getAdditionalData();
      $additionalData['formio_id'] = $formId;
      $servizio->setAdditionalData($additionalData);

    } else {
      $event->getForm()->addError(
        new FormError('Devi selezionare almeno un template per continuare')
      );
    }
    $this->em->persist($servizio);
  }

  /**
   * @param SciaPraticaEdilizia $pratica
   * @param TestiAccompagnatoriProcedura $helper
   * @return array
   */
  private function setupHelperData(FormIO $pratica, TestiAccompagnatoriProcedura $helper)
  {
    return json_encode($pratica->getDematerializedForms());
  }
}
