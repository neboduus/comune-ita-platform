<?php


namespace AppBundle\Services;


use AppBundle\Entity\Servizio;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormError;

class FormServerApiAdapterService
{
  //const FORM_SERVER_URL = 'https://formserver.opencontent.it/';

  const STANDARD_ERROR = 'Si è verificato nella creazione del nuovo form, se il problema persiste contattare un amministratore.';

  protected $formServerUrl = '';

  /**
   * @var LoggerInterface
   */
  protected $logger;


  public function __construct($formServerUrl,  LoggerInterface $logger) {
    $this->formServerUrl = $formServerUrl;
    $this->logger        = $logger;
  }

  /**
   * @param Servizio $servizio
   * @return array
   */
  public function createForm(Servizio $servizio)
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

    $client = new Client(['base_uri' => $this->formServerUrl]);
    $request = new Request(
      'POST',
      $client->getConfig('base_uri') . '/form',
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
      $this->logger->error($e->getMessage());
    } catch (\Exception $e) {
      $error = $e->getMessage();
      $this->logger->error($e->getMessage());
    }

    return [
      'status' => 'error',
      'message' => $error
    ];
  }

  public function cloneForm(Servizio $service, Servizio $serviceToClone)
  {

    $formID = false;
    $flowsteps = $serviceToClone->getFlowSteps();
    if (!empty($flowsteps)) {
      foreach ($flowsteps as $f) {
        if ($f['type'] == 'formio' && $f['parameters']['formio_id'] && !empty($f['parameters']['formio_id'])) {
          $formID = $f['parameters']['formio_id'];
          break;
        }
      }
    }
    // Retrocompatibilità
    if (!$formID) {
      $additionalData = $serviceToClone->getAdditionalData();
      $formID = $additionalData['formio_id'];
    }

    $response = self::getForm($formID);
    if ($response['status'] != 'success') {
      return [
        'status' => 'error',
        'message' => 'Fail on retrive form'
      ];
    }
    $form =$response['form'];

    $form['title'] = $service->getName();
    $form['name'] = $service->getSlug();
    $form['path'] = $service->getSlug();
    $form['description'] = $service->getDescription();

    unset($form['_id'], $form['modified'], $form['created'], $form['__v']);

    $client = new Client(['base_uri' => $this->formServerUrl]);
    $request = new Request(
      'POST',
      $client->getConfig('base_uri') . '/form',
      ['Content-Type' => 'application/json'],
      json_encode($form)
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
      $this->logger->error($e->getMessage());
    } catch (\Exception $e) {
      $error = $e->getMessage();
      $this->logger->error($e->getMessage());
    }

    return [
      'status' => 'error',
      'message' => $error
    ];
  }

  public function getForm( $formID )
  {
    $client = new Client(['base_uri' => $this->formServerUrl]);
    $request = new Request(
      'GET',
      $client->getConfig('base_uri') . '/form/' . $formID,
      ['Content-Type' => 'application/json']
    );

    try {
      $response = $client->send($request);
      if ($response->getStatusCode() == 200) {
        $responseBody = json_decode($response->getBody(), true);
        return [
          'status' => 'success',
          'form' => $responseBody
        ];
      }

    } catch (GuzzleException $e) {
      $error = $e->getMessage();
      $this->logger->error($e->getMessage());
    } catch (\Exception $e) {
      $error = $e->getMessage();
      $this->logger->error($e->getMessage());
    }

    return [
      'status' => 'error',
      'message' => $error
    ];
  }

  public function deleteForm( Servizio $service )
  {

    $formID = false;
    $flowsteps = $service->getFlowSteps();
    if (!empty($flowsteps)) {
      foreach ($flowsteps as $f) {
        if (isset($f['type']) && $f['type'] == 'formio' && isset($f['parameters']['formio_id']) && $f['parameters']['formio_id'] && !empty($f['parameters']['formio_id'])) {
          $formID = $f['parameters']['formio_id'];
          break;
        }
      }
    }
    // Retrocompatibilità
    if (!$formID) {
      $additionalData = $service->getAdditionalData();
      $formID = isset($additionalData['formio_id']) ? $additionalData['formio_id'] : false;
    }

    if ($formID) {
      $client = new Client(['base_uri' => $this->formServerUrl]);
      $request = new Request(
        'DELETE',
        $client->getConfig('base_uri') . '/form/' . $formID,
        ['Content-Type' => 'application/json']
      );

      try {
        $response = $client->send($request);
        if ($response->getStatusCode() == 200) {
          return true;
        }
      } catch (GuzzleException $e) {
        $this->logger->error($e->getMessage());
      } catch (\Exception $e) {
        $this->logger->error($e->getMessage());
      }
    }

    return false;
  }


  /**
   * @param $schema
   * @return array
   */
  public function editForm($schema)
  {
    $client = new Client(['base_uri' => $this->formServerUrl]);
    $request = new Request(
      'PUT',
      $client->getConfig('base_uri') . '/' . $schema['path'],
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
      $this->logger->error($e->getMessage());
    } catch (\Exception $e) {
      $error = $e->getMessage();
      $this->logger->error($e->getMessage());
    }

    return [
      'status' => 'error',
      'message' => $error
    ];
  }

  public  function getFormSchema($formID)
  {
    $client = new Client(['base_uri' => $this->formServerUrl]);
    $request = new Request(
      'GET',
      $client->getConfig('base_uri') . '/form/' . $formID . '/schema',
      ['Content-Type' => 'application/json']
    );

    try {
      $response = $client->send($request);
      if ($response->getStatusCode() == 200) {
        $responseBody = json_decode($response->getBody(), true);
        return [
          'status' => 'success',
          'schema' => $responseBody
        ];
      }

    } catch (GuzzleException $e) {
      $error = $e->getMessage();
      $this->logger->error($e->getMessage());
    } catch (\Exception $e) {
      $error = $e->getMessage();
      $this->logger->error($e->getMessage());
    }

    return [
      'status' => 'error',
      'message' => $error
    ];
  }
}
