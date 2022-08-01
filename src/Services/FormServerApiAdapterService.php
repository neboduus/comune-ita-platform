<?php


namespace App\Services;

use App\Entity\Servizio;
use App\FormIO\FormIOSchemaProviderInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use phpDocumentor\Reflection\Types\Self_;
use Psr\Log\LoggerInterface;

class FormServerApiAdapterService implements FormIOSchemaProviderInterface
{

  const APPLICANT_FORM_SLUG = 'anagrafica';

  const STANDARD_ERROR = 'Si è verificato nella creazione del nuovo form, se il problema persiste contattare un amministratore.';

  protected $formServerUrl = '';

  protected $formServerPublicUrl;

  /**
   * @var LoggerInterface
   */
  protected $logger;

  private static $cache = [];

  public function __construct($formServerUrl, $formServerPublicUrl, LoggerInterface $logger)
  {
    $this->formServerUrl = $formServerUrl;
    $this->formServerPublicUrl = $formServerPublicUrl;
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
   * @return string
   */
  public function getFormServerPublicUrl()
  {
    return $this->formServerPublicUrl;
  }

  /**
   * @param Servizio $servizio
   * @return array
   */
  public function createForm(Servizio $servizio)
  {

    $component = array(
      'label' => 'HTML',
      'attrs' => array(array('attr' => '', 'value' => '',),),
      'content' => 'Come primo componente ti raccomandiamo di inserire il sottoform <strong>Anagrafica</strong>, necessario per la corretta implementazione dei form dinamici.',
      'refreshOnChange' => false,
      'tableView' => false,
      'key' => 'html',
      'type' => 'htmlelement',
      'input' => false,
      'validate' => array('unique' => false, 'multiple' => false,),
    );

    $response = self::getFormBySlug(self::APPLICANT_FORM_SLUG);
    if ($response['status'] == 'success') {
      $component = array(
        "label"=> "applicant",
        "tableView"=> true,
        "form"=> $response['form']['_id'],
        "useOriginalRevision"=> false,
        "reference"=> false,
        "key"=> "applicant",
        "type"=> "form",
        "input"=> true,
        "lazyLoad"=> true
      );
    }

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
                $component
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

  public function getFormBySlug($slug)
  {
    if (!isset(self::$cache[$this->formServerUrl . $slug])) {
      $client = new Client(['base_uri' => $this->formServerUrl]);
      $request = new Request(
        'GET',
        $client->getConfig('base_uri') . '/' . $slug,
        ['Content-Type' => 'application/json']
      );

      try {
        $response = $client->send($request);

        if ($response->getStatusCode() == 200) {
          $responseBody = json_decode($response->getBody(), true);

          self::$cache[$this->formServerUrl . $slug] = [
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

    return self::$cache[$this->formServerUrl . $slug];
  }

  public function deleteForm(Servizio $service)
  {

    $formID = false;
    $flowsteps = $service->getFlowSteps();
    if (!empty($flowsteps)) {
      foreach ($flowsteps as $f) {
        $parameters = $f->getParameters();
        if ($f->getType() == 'formio' && isset($parameters['formio_id']) && !empty($parameters['formio_id'])) {
          $formID = $parameters['formio_id'];
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
        $parameters = $f->getParameters();
        if ($f->getType() == 'formio' && isset($parameters['formio_id']) && !empty($parameters['formio_id'])) {
          $formID = $parameters['formio_id'];
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

  /**
   * @param $formID
   * @return array
   */
  public function getI18nLabels($formID)
  {
    $client = new Client(['base_uri' => $this->formServerUrl]);
    $request = new Request(
      'GET',
      $client->getConfig('base_uri').'/form/'.$formID.'/i18n-labels',
      ['Content-Type' => 'application/json']
    );

    try {
      $response = $client->send($request, ['timeout' => 2]);
      if ($response->getStatusCode() == 200) {
        $responseBody = json_decode($response->getBody(), true);

        return [
          'status' => 'success',
          'data' => $responseBody,
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

  /**
   * @param $formID
   * @return array
   */
  public function getTranslations($formID)
  {
    $client = new Client(['base_uri' => $this->formServerUrl]);
    $request = new Request(
      'GET',
      $client->getConfig('base_uri').'/form/'.$formID.'/i18n',
      ['Content-Type' => 'application/json']
    );

    try {
      $response = $client->send($request);
      if ($response->getStatusCode() == 200) {
        $responseBody = json_decode($response->getBody(), true);

        return [
          'status' => 'success',
          'data' => $responseBody,
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

  public function saveTranslations($formID, $translations, $update = false)
  {
    $method = $update ? 'PUT' : 'POST';
    $client = new Client(['base_uri' => $this->formServerUrl]);
    $request = new Request(
      $method,
      $client->getConfig('base_uri').'/form/'.$formID.'/i18n',
      ['Content-Type' => 'application/json'],
      json_encode($translations, JSON_UNESCAPED_UNICODE)
    );

    try {
      $response = $client->send($request);
      if (in_array($response->getStatusCode(), [200, 201])) {
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

}
