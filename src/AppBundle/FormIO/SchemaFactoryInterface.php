<?php

namespace AppBundle\FormIO;

interface SchemaFactoryInterface
{
  /**
   * @param $formIOId
   * @return Schema
   */
  public function createFromFormId($formIOId);
}
