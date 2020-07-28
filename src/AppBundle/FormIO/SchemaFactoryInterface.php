<?php

namespace AppBundle\FormIO;

interface SchemaFactoryInterface
{
  /**
   * @param string $formIOId
   * @param bool $useCache
   * @return Schema
   */
  public function createFromFormId($formIOId, $useCache = true);
}
