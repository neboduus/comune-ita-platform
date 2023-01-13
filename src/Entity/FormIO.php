<?php

namespace App\Entity;

use App\Form\Admin\Servizio\PaymentDataType;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class FormIO
 * @ORM\Entity
 */
class FormIO extends Pratica implements DematerializedFormPratica
{


  const FIELD_SUBJECT = 'application_subject';

  /**
   * @ORM\Column(type="json", options={"jsonb":true})
   * @var array
   */
  protected $dematerializedForms;

  /**
   * SciaPraticaEdilizia constructor.
   */
  public function __construct()
  {
    parent::__construct();
    $this->type = self::TYPE_FORMIO;
    $this->dematerializedForms = [];
  }

  /**
   * @return array
   */
  public function getDematerializedForms()
  {
    return $this->dematerializedForms;
  }

  /**
   * @param $dematerializedForms
   * @return $this
   */
  public function setDematerializedForms($dematerializedForms)
  {
    $this->dematerializedForms = $dematerializedForms;

    return $this;
  }

  public function getPaymentAmount()
  {

    // Recupero l'importo dal form
    if (isset($this->dematerializedForms['flattened'][PaymentDataType::PAYMENT_AMOUNT]) && $this->dematerializedForms['flattened'][PaymentDataType::PAYMENT_AMOUNT]) {
      return str_replace(',', '.', $this->dematerializedForms['flattened'][PaymentDataType::PAYMENT_AMOUNT]);
    }
    // Recupero l'importo dal servizio
    if (isset($this->getServizio()->getPaymentParameters()['total_amounts'])) {
      return str_replace(',', '.', $this->getServizio()->getPaymentParameters()['total_amounts']);
    }

    return 0;
  }

  public function setPaymentAmount($amount)
  {
    $dematerializedForms = $this->dematerializedForms;
    $dematerializedForms['data'][PaymentDataType::PAYMENT_AMOUNT] = $amount;
    $dematerializedForms['flattened'][PaymentDataType::PAYMENT_AMOUNT] = $amount;
    $this->dematerializedForms = $dematerializedForms;
  }

  public function isPaymentExempt()
  {
    $data = $this->dematerializedForms;
    return isset($data['flattened'][PaymentDataType::PAYMENT_AMOUNT]) && $data['flattened'][PaymentDataType::PAYMENT_AMOUNT] <= 0;
  }

  public function getType(): string
  {
    return Pratica::TYPE_FORMIO;
  }

  public function generateSubject(): string
  {
    $data = $this->dematerializedForms;
    if (isset($data['flattened'][self::FIELD_SUBJECT]) && !empty($data['flattened'][self::FIELD_SUBJECT])) {
      return $data['flattened'][self::FIELD_SUBJECT] . ' '. $this->getId();
    }
    return parent::generateSubject();
  }

}
