<?php

namespace AppBundle\FormIO;

use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class SchemaFactory implements SchemaFactoryInterface
{
  /**
   * @var array
   * @todo usare un FormIOSchemaProviderInterface decorator
   */
  private static $cacheRemoteForms = [];

  /**
   * @var Schema[]
   * @todo usare uno SchemaFactoryInterface decorator
   */
  private static $cacheSchemas = [];

  private $provider;

  private $httpClient;

  private $session;

  private $useCache = true;

  public function __construct(
    FormIOSchemaProviderInterface $provider,
    HttpClientInterface $httpClient,
    SessionInterface $session
  ) {
    $this->provider = $provider;
    $this->httpClient = $httpClient;
    $this->session = $session;
  }

  /**
   * @param $formIOId
   * @return Schema
   */
  public function createFromFormId($formIOId)
  {
    if (empty(trim($formIOId))) {
      return new Schema();
    }

    $cacheId = $this->provider->getFormServerUrl().$formIOId;

    if ($this->useCache && $this->hasCache($cacheId)) {
      return $this->getCache($cacheId);
    }

    $schema = new Schema();
    $schema->setId($formIOId);
    $schema->setServer($this->provider->getFormServerUrl());
    $this->parseForm($schema, $formIOId);

    if ($this->useCache && $schema->countComponents() > 0) {
      $this->setCache($cacheId, $schema);
    }

    return $schema;
  }

  private function hasCache($id)
  {
    if ($this->session->isStarted()) {
      return $this->session->has('form.factory.'.$id);
    }

    return isset(self::$cacheSchemas[$id]);
  }

  private function getCache($id)
  {
    if ($this->session->isStarted()) {
      return $this->session->get('form.factory.'.$id);
    }

    return self::$cacheSchemas[$id];
  }

  private function parseForm(Schema $schema, $formIOId, $prefixKey = null, $prefixLabel = null)
  {
    $form = $this->getRemoteSchema($formIOId);
    if ($form) {
      $schema->addSource($formIOId, $form);
      $this->parseComponent($schema, $form, $prefixKey, $prefixLabel);
    }
  }

  private function getRemoteSchema($formIOId)
  {
    if (!isset(self::$cacheRemoteForms[$formIOId])) {
      $adapterResult = $this->provider->getForm($formIOId);
      if ($adapterResult['status'] == 'success') {
        self::$cacheRemoteForms[$formIOId] = $adapterResult['form'];
      } else {
        return false;
      }
    }

    return self::$cacheRemoteForms[$formIOId];
  }

  private function parseComponent(Schema $schema, $component, $prefixKey = null, $prefixLabel = null)
  {
    if (!isset($component['type']) && isset($component['components'])) {
      foreach ($component['components'] as $item) {
        $this->parseComponent($schema, $item, $prefixKey, $prefixLabel);
      }
    } else {
      switch ($component['type']) {
        case 'form':
        case 'panel':
        case 'column':
          if ($component['type'] == 'form' && isset($component['form'])) {
            if (isset($component['key'])) {
              $prefixKey .= $component['key'].'.';
            }
            $this->parseForm($schema, $component['form'], $prefixKey, $prefixLabel);
          }

          if ($component['type'] == 'panel' && isset($component['title'])) {
            $prefixLabel .= $component['title'].'/';
          }

          if (isset($component['components'])) {
            foreach ($component['components'] as $item) {
              $this->parseComponent($schema, $item, $prefixKey, $prefixLabel);
            }
          }
          break;

        case 'columns':
          if (isset($component['columns'])) {
            foreach ($component['columns'] as $item) {
              $this->parseComponent($schema, $item, $prefixKey, $prefixLabel);
            }
          }
          break;

        case 'email':
          $schema->addComponent(
            $prefixKey.$component['key'],
            Type\EmailType::class,
            $this->parseComponentOptions($component, $prefixLabel)
          );
          break;

        case 'financial_report':
          foreach ($component['data']['values'] as $index => $value) {
            foreach ($value as $key => $item) {
              $schema->addComponent(
                $prefixKey.$component['key'].'.'.$index.'.'.$key,
                Type\TextareaType::class,
                $this->parseComponentOptions($component, $prefixLabel)
              );
            }
          }
          $schema->addComponent(
            $prefixKey.$component['key'],
            Type\EmailType::class,
            $this->parseComponentOptions($component, $prefixLabel)
          );
          break;

        case 'checkbox':
          $schema->addComponent(
            $prefixKey.$component['key'],
            Type\CheckboxType::class,
            $this->parseComponentOptions($component, $prefixLabel)
          );
          break;

        case 'radio':
          $choices = [];
          if (isset($component['values'])) {
            $choices = array_column($component['values'], 'value');
          }
          $schema->addComponent(
            $prefixKey.$component['key'],
            Type\ChoiceType::class,
            $this->parseComponentOptions(
              $component,
              $prefixLabel,
              [
                'choices' => $choices,
              ]
            )
          );
          break;

        case 'number':
          $schema->addComponent(
            $prefixKey.$component['key'],
            Type\NumberType::class,
            $this->parseComponentOptions($component, $prefixLabel)
          );
          break;

        case 'file': //@todo
          $schema->addComponent(
            $prefixKey.$component['key'],
            Type\TextType::class,
            $this->parseComponentOptions($component, $prefixLabel)
          );
          break;

        case 'hidden':
          $schema->addComponent(
            $prefixKey.$component['key'],
            Type\HiddenType::class,
            $this->parseComponentOptions($component, $prefixLabel)
          );
          break;

        case 'textarea':
          $schema->addComponent(
            $prefixKey.$component['key'],
            Type\TextareaType::class,
            $this->parseComponentOptions($component, $prefixLabel)
          );
          break;

        case 'url':
          $schema->addComponent(
            $prefixKey.$component['key'],
            Type\UrlType::class,
            $this->parseComponentOptions($component, $prefixLabel)
          );
          break;

        case 'currency':
          $schema->addComponent(
            $prefixKey.$component['key'],
            Type\CurrencyType::class,
            $this->parseComponentOptions($component, $prefixLabel)
          );
          break;

        case 'time':
          $schema->addComponent(
            $prefixKey.$component['key'],
            Type\TimeType::class,
            $this->parseComponentOptions($component, $prefixLabel)
          );
          break;

        case 'day':
          foreach ($component['fields'] as $key => $item) {
            $schema->addComponent(
              $prefixKey.$component['key'].'.'.$key,
              Type\NumberType::class,
              $this->parseComponentOptions(
                $component,
                $prefixLabel,
                [
                  'label' => $component['label'].' '.$key,
                ]
              )
            );
          }
          break;

        case 'phonenumber': //@todo
        case 'textfield':
          $schema->addComponent(
            $prefixKey.$component['key'],
            Type\TextType::class,
            $this->parseComponentOptions($component, $prefixLabel)
          );
          break;

        case 'date':
        case 'datetime':
          $schema->addComponent(
            $prefixKey.$component['key'],
            Type\DateTimeType::class,
            $this->parseComponentOptions(
              $component,
              $prefixLabel,
              [
                'format' => Type\DateTimeType::HTML5_FORMAT,
                'widget' => 'single_text',
              ]
            )
          );
          break;

        case 'select':
          $choices = [];
          $data = $component['data'];
          if (isset($component['dataSrc']) && isset($component['valueProperty'])) {
            try {
              $url = $data[$component['dataSrc']];
              /** @var ResponseInterface $remoteResponse */
              $remoteResponse = $this->httpClient->request('GET', $url);
              $remoteData = (array)json_decode($remoteResponse->getContent(), true);
              $property = $component['valueProperty'];
              foreach ($remoteData as $item) {
                if (isset($item[$property])) {
                  $choices[] = $item[$property];
                } elseif (is_string($item)) {
                  $choices[] = $item;
                }
              }
            } catch (\Throwable $e) {
              //@todo log error
            }
          } elseif (isset($data['values'])) {
            $choices = array_column($data['values'], 'value');
          }
          $schema->addComponent(
            $prefixKey.$component['key'],
            Type\ChoiceType::class,
            $this->parseComponentOptions(
              $component,
              $prefixLabel,
              [
                'choices' => $choices,
              ]
            )
          );
          break;
      }
    }
  }

  private function parseComponentOptions($component, $prefixLabel = null, $options = [])
  {
    if (!isset($options['required'])) {
      $required = isset($component['validate']['required']) && $component['validate']['required'];
      $options['required'] = $required;
    }

    if (!isset($options['invalid_message'])) {
      $message = false;
      if (isset($component['validate']['customMessage']) && !empty($component['validate']['customMessage'])) {
        $message = $component['validate']['customMessage'];
      } elseif (isset($component['errorLabel']) && !empty($component['errorLabel'])) {
        $message = $component['errorLabel'];
      }
      if ($message) {
        $options['invalid_message'] = $message;
      }
    }

    if (isset($component['label']) && !isset($options['label'])) {
      $options['label'] = $component['label'];
    }
    if (isset($options['label']) && $prefixLabel) {
      $options['label'] = $prefixLabel.$options['label'];
    }

    $constraints = [];
    if ($options['required']) {
      $constraints[] = new NotBlank();
    }
    if (isset($component['validate']['pattern']) && !empty($component['validate']['pattern'])) {
      $constraints[] = new Regex(['pattern' => '/'.$component['validate']['pattern'].'/']);
    }
    if (count($constraints) > 0) {
      $options['constraints'] = $constraints;
    }

    $options['mapped'] = false;

    return $options;
  }

  private function setCache($id, $data)
  {
    if ($this->session->isStarted()) {
      $this->session->set('form.factory.'.$id, $data);
    }
    self::$cacheSchemas[$id] = $data;
  }
}
