<?php

namespace App\Utils;

use Symfony\Component\Form\FormInterface;
use GuzzleHttp\Client;

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

  /**
   * @param string $url
   * @return boolean
   */
  public static function isUrlValid(?string $url) 
  {
    try {
      $client = new Client();
      $response = $client->request('GET', $url);
      return $response->getStatusCode() == 200;
    } catch (\Exception $e) {
      return false;
    }
  }

  /**
   * Checks if an array has at least one key with an empty value
   * 
   * @param array $array
   * @return boolean
   */
  public static function isArrayValueEmpty(?array $array) 
  {
    $isEmpty = false;
    
    array_walk_recursive($array, function ($leaf) use (&$isEmpty) {
      if ($leaf !== "") {
        return;
      }
      $isEmpty = true;
    });

    return $isEmpty;
  }
}
