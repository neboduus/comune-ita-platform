<?php


namespace AppBundle\Services;

use AppBundle\Entity\Servizio;
use AppBundle\FormIO\FormIOSchemaProviderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Psr\Log\LoggerInterface;

class FormServerApiAdapterService implements FormIOSchemaProviderInterface
{
  //const FORM_SERVER_URL = 'https://formserver.opencontent.it/';

  const STANDARD_ERROR = 'Si è verificato nella creazione del nuovo form, se il problema persiste contattare un amministratore.';

  protected $formServerUrl = '';

  /**
   * @var LoggerInterface
   */
  protected $logger;

  private static $cache = [];

  public function __construct($formServerUrl, LoggerInterface $logger)
  {
    $this->formServerUrl = $formServerUrl;
    $this->logger = $logger;
  }

  /**
   * @return string
   */
  public function getFormServerUrl()
  {
    return $this->formServerUrl;
  }

  /**
   * @param Servizio $servizio
   * @return array
   */
  public function createForm(Servizio $servizio)
  {
    $schema = [
      'display' => 'wizard',
      'type' => 'form',
      'components' =>
        array(
          array(
            'label' => 'Panel',
            'title' => 'Richiedente',
            'breadcrumbClickable' => true,
            'collapsible' => false,
            'mask' => false,
            'tableView' => false,
            'alwaysEnabled' => false,
            'type' => 'panel',
            'input' => false,
            'components' =>
              array(
                array(
                  'label' => 'Avvertenza',
                  'tag' => 'h6',
                  'attrs' => array(array('attr' => '', 'value' => '',),),
                  'content' => 'Benvenuto nella configurazione del tuo nuovo form!',
                  'refreshOnChange' => false,
                  'tableView' => false,
                  'key' => 'avvertenza',
                  'type' => 'htmlelement',
                  'input' => false,
                  'validate' => array('unique' => false, 'multiple' => false,),
                ),
                array(
                  'label' => 'HTML',
                  'attrs' => array(array('attr' => '', 'value' => '',),),
                  'content' => 'Come primo componente ti raccomandiamo di inserire il sottoform <strong>Anagrafica</strong>, necessario per la corretta implementazione dei form dinamici.',
                  'refreshOnChange' => false,
                  'tableView' => false,
                  'key' => 'html',
                  'type' => 'htmlelement',
                  'input' => false,
                  'validate' => array('unique' => false, 'multiple' => false,),
                ),
              ),
            'key' => 'panel',
            'collapsed' => false,
            'reorder' => false,
            'validate' => array('unique' => false, 'multiple' => false,),
          ),
        ),
      'tags' => ['custom'],
      'title' => $servizio->getName(),
      'name' => $servizio->getSlug(),
      'path' => $servizio->getSlug(),
      'description' => $servizio->getName().' - '.$servizio->getEnte()->getName(),
    ];

    $client = new Client(['base_uri' => $this->formServerUrl]);
    $request = new Request(
      'POST',
      $client->getConfig('base_uri').'/form',
      ['Content-Type' => 'application/json'],
      json_encode($schema)
    );

    try {

      $response = $client->send($request);
      if ($response->getStatusCode() == 201) {
        $responseBody = json_decode($response->getBody(), true);

        return [
          'status' => 'success',
          'form_id' => $responseBody['_id'],
        ];
      }

      $error = self::STANDARD_ERROR;

    } catch (\Throwable $e) {
      $error = $e->getMessage();
      $this->logger->error($e->getMessage());
    }

    return [
      'status' => 'error',
      'message' => $error,
    ];
  }

  /**
   * @param Servizio $service
   * @param $remoteUrl
   * @return array
   */
  public function cloneFormFromRemote(Servizio $service, $remoteUrl)
  {
    $error = self::STANDARD_ERROR;
    try {
      $client = new Client();
      $request = new Request(
        'GET',
        $remoteUrl,
        ['Content-Type' => 'application/json']
      );
      $response = $client->send($request);
      if ($response->getStatusCode() == 200) {
        $schema = json_decode($response->getBody(), true);
        $schema['title'] = $service->getName();
        $schema['name'] = $service->getSlug();
        $schema['path'] = $service->getSlug();
        $schema['description'] = $service->getName();
        unset($schema['_id'], $schema['modified'], $schema['created'], $schema['__v']);
        $response = $this->createFormFromSchema($schema);
        if ($response['status'] == 'success') {
          return [
            'status' => 'success',
            'form_id' => $response['form_id'],
          ];
        }
      }
    } catch (\Throwable $e) {
      $error = $e->getMessage();
      $this->logger->error($e->getMessage());
    }

    return [
      'status' => 'error',
      'message' => $error,
    ];
  }

  /**
   * @param $schema
   * @return array
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function createFormFromSchema($schema)
  {
    $client = new Client(['base_uri' => $this->formServerUrl]);
    $request = new Request(
      'POST',
      $client->getConfig('base_uri').'/form',
      ['Content-Type' => 'application/json'],
      json_encode($schema)
    );

    $response = $client->send($request);
    if ($response->getStatusCode() == 201) {
      $responseBody = json_decode($response->getBody(), true);

      return [
        'status' => 'success',
        'form_id' => $responseBody['_id'],
      ];
    }
    throw new \RuntimeException("Error creating form from schema");
  }

  /**
   * @param Servizio $service
   * @param Servizio $serviceToClone
   * @return array
   */
  public function cloneForm(Servizio $service, Servizio $serviceToClone)
  {
    $formID = $serviceToClone->getFormIoId();
    $response = self::getForm($formID);
    if ($response['status'] != 'success') {
      return [
        'status' => 'error',
        'message' => 'Fail on retrive form',
      ];
    }
    $form = $response['form'];

    $form['title'] = $service->getName();
    $form['name'] = $service->getSlug();
    $form['path'] = $service->getSlug();
    $form['description'] = $service->getDescription() !== "" ? $service->getDescription() : $service->getName();

    unset($form['_id'], $form['modified'], $form['created'], $form['__v']);

    $client = new Client(['base_uri' => $this->formServerUrl]);
    $request = new Request(
      'POST',
      $client->getConfig('base_uri').'/form',
      ['Content-Type' => 'application/json'],
      json_encode($form)
    );

    try {
      $response = $client->send($request);
      if ($response->getStatusCode() == 201) {
        $responseBody = json_decode($response->getBody(), true);

        return [
          'status' => 'success',
          'form_id' => $responseBody['_id'],
        ];
      }

      $error = self::STANDARD_ERROR;

    } catch (\Throwable $e) {
      $error = $e->getMessage();
      $this->logger->error($e->getMessage());
    }

    return [
      'status' => 'error',
      'message' => $error,
    ];
  }

  public function getForm($formID)
  {
    if (!isset(self::$cache[$this->formServerUrl.$formID])) {
      $client = new Client(['base_uri' => $this->formServerUrl]);
      $request = new Request(
        'GET',
        $client->getConfig('base_uri').'/form/'.$formID,
        ['Content-Type' => 'application/json']
      );

      try {
        $response = $client->send($request);

        if ($response->getStatusCode() == 200) {
          $responseBody = json_decode($response->getBody(), true);

          self::$cache[$this->formServerUrl.$formID] = [
            'status' => 'success',
            'form' => $responseBody
          ];
        }else {
          throw new \Exception("Unexpected status response");
        }

      } catch (\Throwable $e) {
        $error = $e->getMessage();
        $this->logger->error($e->getMessage());

        return [
          'status' => 'error',
          'message' => $error
        ];
      }
    }

    return self::$cache[$this->formServerUrl.$formID];
  }

  public function deleteForm(Servizio $service)
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
        $client->getConfig('base_uri').'/form/'.$formID,
        ['Content-Type' => 'application/json']
      );

      try {
        $response = $client->send($request);
        if ($response->getStatusCode() == 200) {
          return true;
        }
      } catch (\Throwable $e) {
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
      $client->getConfig('base_uri').'/'.$schema['path'],
      ['Content-Type' => 'application/json'],
      json_encode($schema)
    );

    try {
      $response = $client->send($request);
      if ($response->getStatusCode() == 200) {
        return [
          'status' => 'success',
        ];
      }

      throw new \Exception("Unexpected status response");

    } catch (\Throwable $e) {
      $error = $e->getMessage();
      $this->logger->error($e->getMessage());

      return [
        'status' => 'error',
        'message' => $error,
      ];
    }
  }

  public function getFormSchema($formID)
  {
    $client = new Client(['base_uri' => $this->formServerUrl]);
    $request = new Request(
      'GET',
      $client->getConfig('base_uri').'/form/'.$formID.'/schema',
      ['Content-Type' => 'application/json']
    );

    try {
      $response = $client->send($request);
      if ($response->getStatusCode() == 200) {
        $responseBody = json_decode($response->getBody(), true);

        return [
          'status' => 'success',
          'schema' => $responseBody,
        ];
      }

      throw new \Exception("Unexpected status response");

    } catch (\Throwable $e) {
      $error = $e->getMessage();
      $this->logger->error($e->getMessage());

      return [
        'status' => 'error',
        'message' => $error,
      ];
    }
  }

  public function getFormIdFromService(Servizio $service)
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

    return $formID;
  }
}
