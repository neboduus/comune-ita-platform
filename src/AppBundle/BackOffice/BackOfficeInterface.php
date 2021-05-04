<?php


namespace AppBundle\BackOffice;


use AppBundle\Entity\Pratica;

interface BackOfficeInterface
{
  const INTEGRATION_STATUSES = [
    'Nessuna integrazione prevista' => 0,
    'Pratica pagata' => Pratica::STATUS_PAYMENT_SUCCESS,
    'Pratica inviata' => Pratica::STATUS_PRE_SUBMIT,
    'Pratica acquisita' => Pratica::STATUS_SUBMITTED,
    'Pratica protocollata' => Pratica::STATUS_REGISTERED,
    'Pratica presa in carico' => Pratica::STATUS_PENDING,
    'Pratica accettata' => Pratica::STATUS_COMPLETE,
    'Pratica rifiutata' => Pratica::STATUS_CANCELLED
  ];

  public function getIdentifier();

  public function getName();

  public function getPath();

  public function getRequiredFields();

  public function getAllowedActivationPoints();

  public function checkRequiredFields($schema);

  public function execute($data);
}
