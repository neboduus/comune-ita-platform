<?php

namespace App;

use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;

final class InstanceKernelFactory
{

  /**
   * @param string $env
   * @param Request $request
   * @param bool $debug
   * @return InstanceKernel|null
   */
  public static function instanceFromRequest(string $env, Request $request, bool $debug): ?InstanceKernel
  {
    $instanceParams = self::parseYaml($env);

    $host = $request->getHost();
    $pathInfoParts = explode('/', trim($request->getPathInfo(), '/'));
    $path = $pathInfoParts[0] ?? null;

    $instance = false;
    if (isset($instanceParams[$host . '/' . $path])) {
      $instance = $instanceParams[$host . '/' . $path];
    }

    if ($instance) {
      $instance['ocsdc_host'] = $host;
      $instance['prefix'] = $path;
      $kernel = new InstanceKernel($env, $debug);
      $kernel->setIdentifier($instance['identifier']);
      $kernel->setInstanceParameters($instance);

      return $kernel;
    }

    return null;
  }


  /**
   * @param string $env
   * @param string $instance
   * @param bool $debug
   * @return InstanceKernel
   * @throws Exception
   */
  public static function instanceFromConsole(string $env, string $instance, bool $debug): InstanceKernel
  {
    $instanceParams = self::parseYaml($env);
    $params = $host = false;

    foreach ($instanceParams as $k => $v) {
      if ($v['identifier'] == $instance) {
        $params = $v;
        $host = $k;
      }
    }

    if (!$params) {
      throw new Exception("Instance $instance not found");
    }

    $pathInfoParts = explode('/', trim($host, '/'));
    $path = $pathInfoParts[1] ?? null;
    $params['prefix'] = $path;

    $kernel = new InstanceKernel($env, $debug);
    $kernel->setIdentifier($instance);
    $kernel->setInstanceParameters($params);

    return $kernel;
  }

  /**
   * @param $env
   * @return mixed
   */
  private static function parseYaml($env)
  {
    $instances = Yaml::parse(file_get_contents(__DIR__.'/../config/instances_'.$env.'.yml'));
    return $instances['instances'];
  }
}
