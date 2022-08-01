<?php

namespace App\Utils;

use Symfony\Component\Form\FormInterface;

class FormUtils
{
  /**
   * @param FormInterface $form
   * @return array
   */
  public static function getErrorsFromForm(FormInterface $form): array
  {
    $errors = [];
    foreach ($form->getErrors() as $error) {
      $message = $error->getMessage() . ': '. $error->getOrigin()->getName();
      //$message .= is_array($error->getOrigin()->getViewData()) ? implode(', ', $error->getOrigin()->getViewData()) : $error->getOrigin()->getViewData();
      $errors[] = $message;
    }
    foreach ($form->all() as $childForm) {
      if ($childForm instanceof FormInterface) {
        if ($childErrors = self::getErrorsFromForm($childForm)) {
          $errors[] = $childErrors;
        }
      }
    }
    return $errors;
  }
}
