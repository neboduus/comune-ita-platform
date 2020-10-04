<?php

namespace App\FormIO;

interface FormIOSchemaProviderInterface
{
  /**
   * @param string $formIOId
   * @return array $data = [
   *     'message' => 'success',
   *     'form' => [],
   * ]
   */
  public function getForm($formIOId);

  /**
   * @return string
   */
  public function getFormServerUrl();
}
