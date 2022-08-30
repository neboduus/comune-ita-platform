<?php

namespace App\FormIO;

interface SchemaFactoryInterface
{
  /**
   * @param $formIOId
   * @return Schema
   */
  public function createFromFormId($formIOId);
}
