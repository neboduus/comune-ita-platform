<?php


namespace App\BackOffice;


use App\Entity\Pratica;

interface BackOfficeInterface
{
  const INTEGRATION_STATUSES = [
    'backoffice.integration.no_integration' => 0,
    'STATUS_APPLICATION_PAID' => Pratica::STATUS_PAYMENT_SUCCESS,
    'STATUS_APPLICATION_PRE_SUBMIT' => Pratica::STATUS_PRE_SUBMIT,
    'STATUS_APPLICATION_SUBMITTED' => Pratica::STATUS_SUBMITTED,
    'STATUS_APPLICATION_REGISTERED' => Pratica::STATUS_REGISTERED,
    'STATUS_APPLICATION_ASSIGNMENT' => Pratica::STATUS_PENDING,
    'STATUS_APPLICATION_COMPLETED' => Pratica::STATUS_COMPLETE,
    'STATUS_APPLICATION_CANCELLED' => Pratica::STATUS_CANCELLED
  ];

  public function getIdentifier();

  public function getName();

  public function getPath();

  public function getRequiredFields();

  public function getAllowedActivationPoints();

  public function isAllowedActivationPoint($activationPoint);

  public function checkRequiredFields($schema);

  public function execute($data);
}
