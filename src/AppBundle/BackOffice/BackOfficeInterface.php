<?php


namespace AppBundle\BackOffice;


interface BackOfficeInterface
{

  public function getName();

  public function getRequiredFields();

  public function execute($data);
}
