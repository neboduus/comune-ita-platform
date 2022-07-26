<?php

namespace AppBundle\Payment\Gateway;


use AppBundle\Entity\Pratica;
use AppBundle\Payment\AbstractPaymentData;
use AppBundle\Payment\PaymentDataInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;

class Bollo extends AbstractPaymentData implements EventSubscriberInterface
{

  public function getIdentifier(): string
  {
    return 'bollo';
  }

  public static function getPaymentParameters()
  {
    return [];
  }

  public static function getFields()
  {
    return array(
      'bollo_identifier',
      'bollo_data_emissione',
      'bollo_ora_emissione',
    );
  }


  /** Event Subscriber **/
  public static function getSubscribedEvents()
  {
    return array(
      FormEvents::PRE_SET_DATA => 'onPreSetData',
      FormEvents::PRE_SUBMIT => 'onPreSubmit'
    );
  }

  /**
   * @param $data
   * @return mixed|void
   */
  public static function getSimplifiedData($data)
  {
    $bolloPaymentData = self::fromData($data);

    $emissionHour = $bolloPaymentData->getFieldValue('bollo_ora_emissione');
    foreach ($emissionHour as $k => $v) {
      $emissionHour[$k] = str_pad($v, 2, '0', STR_PAD_LEFT);
    }

    return [
      'status' => PaymentDataInterface::STATUS_PAYMENT_PAID,
      'bollo_identifier' => $bolloPaymentData->getFieldValue('bollo_identifier'),
      'bollo_emission_date' => $bolloPaymentData->getFieldValue('bollo_data_emissione'),
      'bollo_emission_hour' => implode(':', $emissionHour)
    ];
  }


  public function onPreSetData(FormEvent $event)
  {
    /** @var Pratica $pratica */
    $pratica = $event->getData();
    $paymentData = parent::fromData($pratica->getPaymentData());
    $identifier = $paymentData->getFieldValue('bollo_identifier') ? $paymentData->getFieldValue('bollo_identifier') : null;
    $day = $paymentData->getFieldValue('bollo_data_emissione') ? $paymentData->getFieldValue('bollo_data_emissione') : null;
    $hour = $paymentData->getFieldValue('bollo_ora_emissione') ? implode(':', $paymentData->getFieldValue('bollo_ora_emissione')) : null;

    $form = $event->getForm();
    $form
      ->add('bollo_identifier', TextType::class, [
          'label' => 'Inserisci identificativo bollo',
          'data' => (string)$identifier,
          'mapped' => false,
          'required' => true]
      )
      ->add('bollo_data_emissione', DateType::class, [
        'required' => true,
        'mapped' => false,
        'label' => 'Inserisci data emissione bollo',
        'widget' => 'single_text',
        'data' => new \DateTime($day)
      ])
      ->add('bollo_ora_emissione', TimeType::class, [
        'input' => 'datetime',
        'widget' => 'choice',
        'with_seconds' => true,
        'mapped' => false,
        'data' => new \DateTime($hour),
      ]);
  }


  public function onPreSubmit(FormEvent $event)
  {
    $data = $event->getData();
    $paymentData = parent::fromData($event->getData());
    $data['payment_data'] = $paymentData->toJson();
    $event->setData($data);
  }

}
