<?php


namespace AppBundle\Services;


use AppBundle\Entity\Servizio;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Form\FormError;

class FormServerApiAdapterService
{
  const FORM_SERVER_URL = 'https://formserver.opencontent.it/';

  const STANDARD_ERROR = 'Si è verificato nella creazione del nuovo form, se il problema persiste contattare un amministratore.';

  /**
   * @param Servizio $servizio
   * @return array
   */
  public static function createService(Servizio $servizio)
  {
    $schema = [
      'display'    => 'form',
      'type'       => 'form',
      'components' => [],
      'tags'       => ['custom'],
      'title'      => $servizio->getName(),
      'name'       => $servizio->getSlug(),
      'path'       => $servizio->getSlug(),
      'description'=> $servizio->getName() . ' - ' . $servizio->getEnte()->getName()

    ];

    $client = new Client(['base_uri' => self::FORM_SERVER_URL]);
    $request = new Request(
      'POST',
      $client->getConfig('base_uri') . 'form',
      ['Content-Type' => 'application/json'],
      json_encode($schema)
    );

    try {

      $response = $client->send($request);
      if ($response->getStatusCode() == 201) {
        $responseBody = json_decode($response->getBody(), true);

        return [
          'status' => 'success',
          'form_id' => $responseBody['_id']
        ];
      }

      $error = self::STANDARD_ERROR;

    } catch (GuzzleException $e) {
      $error = $e->getMessage();
    } catch (\Exception $e) {
      $error = $e->getMessage();
    }

    return [
      'status' => 'error',
      'message' => $error
    ];
  }

  /**
   * @param $schema
   * @return array
   */
  public static function editService($schema)
  {
    $client = new Client(['base_uri' => self::FORM_SERVER_URL]);
    $request = new Request(
      'PUT',
      $client->getConfig('base_uri') . $schema['path'],
      ['Content-Type' => 'application/json'],
      json_encode($schema)
    );

    try {
      $response = $client->send($request);
      if ($response->getStatusCode() == 200) {
        return [
          'status' => 'success'
        ];
      }

    } catch (GuzzleException $e) {
      $error = $e->getMessage();
    } catch (\Exception $e) {
      $error = $e->getMessage();
    }

    return [
      'status' => 'error',
      'message' => $error
    ];
  }

  public static function getServiceLabels($formID)
  {
    $client = new Client(['base_uri' => self::FORM_SERVER_URL]);
    $request = new Request(
      'GET',
      $client->getConfig('base_uri') . 'form/' . $formID . '/schema',
      ['Content-Type' => 'application/json']
    );

    try {
      $response = $client->send($request);
      if ($response->getStatusCode() == 200) {
        $responseBody = json_decode($response->getBody(), true);
        return [
          'status' => 'success',
          'labels' => $responseBody
        ];
      }

    } catch (GuzzleException $e) {
      $error = $e->getMessage();
    } catch (\Exception $e) {
      $error = $e->getMessage();
    }

    return [
      'status' => 'error',
      'message' => $error
    ];
  }
}
