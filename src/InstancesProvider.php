<?php

namespace App;

use InstanceKernel;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Yaml\Yaml;

class InstancesProvider
{
  private static $instance;

  private static $instances;

  public static function factory()
  {
    if (self::$instance === null) {
      self::$instance = new InstancesProvider();
    }

    return self::$instance;
  }

  private function __construct()
  {
    $this->provideInstances();
  }

  public function match($host, $path)
  {
    foreach (self::$instances['paths'] as $item) {
      if ($item['uri'] == $host && (
          (!empty($item['path']) && $item['path'] == $path)
          || empty($item['path'])
          || (empty($item['path']) && $path == InstanceKernel::DEFAULT_PREFIX)
        )) {

        $identifier = $item['instance'];

        if (isset(self::$instances['params'][$identifier])) {
          if (!empty($item['path'])) {
            self::$instances['params'][$identifier]['prefix'] = $item['path'];
          } elseif ($path != InstanceKernel::DEFAULT_PREFIX) {
            return new RedirectResponse('/' . InstanceKernel::DEFAULT_PREFIX, 302);
          }
          return $identifier;
        }
      }
    }

    return null;
  }

  public function getInstances()
  {
    return self::$instances['params'];
  }

  public function getInstance($identifier)
  {
    return self::$instances['params'][$identifier];
  }

  public function hasInstance($identifier)
  {
    return isset(self::$instances['params'][$identifier]);
  }

  private function provideInstances()
  {
    if (self::$instances === null) {
      self::$instances = $this->provideInstancesFromYaml();
    }
  }

  private function provideInstancesFromYaml()
  {
    $data = [
      'params' => [],
    ];

    $env = $_SERVER['APP_ENV'];

    $instancesFilepath = false;
    if (file_exists(__DIR__ . '/../config/instances_' . $env . '.yml')) {
      $instancesFilepath = __DIR__ . '/../config/instances_' . $env . '.yml';
    } elseif (file_exists(__DIR__ . '/../config/instances.yml')) {
      $instancesFilepath = __DIR__ . '/../config/instances.yml';
    }
    if ($instancesFilepath) {
      $instances = Yaml::parse(file_get_contents($instancesFilepath));
      $instanceParams = $instances['instances'];


      $data = [
        'params' => $instanceParams,
      ];
    }

    return $data;
  }

}
