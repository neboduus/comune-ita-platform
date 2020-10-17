<?php

use App\Kernel;
use App\InstanceKernel;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;

require dirname(__DIR__).'/config/bootstrap.php';

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
    Debug::enable();
}

if ($trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? false) {
    Request::setTrustedProxies(explode(',', $trustedProxies), Request::HEADER_X_FORWARDED_ALL ^ Request::HEADER_X_FORWARDED_HOST);
}

if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? false) {
    Request::setTrustedHosts([$trustedHosts]);
}



$currentInstance = false;
$instances = Yaml::parse(file_get_contents(__DIR__.'/../config/instances_dev.yml'));
$instancePaths = $instances['paths'];
$instanceParams = $instances['instances'];

usort(
  $instancePaths,
  function ($a, $b) {
    $aPath = $a['uri'].'/'.$a['path'];
    $bPath = $b['uri'].'/'.$b['path'];
    if (mb_strlen($aPath) == mb_strlen($bPath)) {
      return 0;
    }

    return (mb_strlen($aPath) < mb_strlen($bPath)) ? 1 : -1;
  }
);

$request = Request::createFromGlobals();
$host = $request->getHost();
$pathInfoParts = explode('/', trim($request->getPathInfo(), '/'));
$path = isset($pathInfoParts[0]) ? $pathInfoParts[0] : null;

$identifier = false;
foreach ($instancePaths as $item) {
  if ($item['uri'] == $host && (
      (!empty($item['path']) && $item['path'] == $path)
      || empty($item['path'])
      || (empty($item['path']) && $path == InstanceKernel::DEFAULT_PREFIX)
    )) {

    $identifier = $item['instance'];

    if (isset($instanceParams[$identifier])) {
      if (!empty($item['path'])) {
        $instanceParams[$identifier]['prefix'] = $item['path'];
      } elseif ($path != InstanceKernel::DEFAULT_PREFIX) {
        $response = new RedirectResponse('/'.InstanceKernel::DEFAULT_PREFIX, 302);
        $response->send();
        exit;
      }
      break;
    }

  }
}

if ($identifier && isset($instanceParams[$identifier])) {
  $kernel = new InstanceKernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
  $kernel->setIdentifier($identifier);
  $kernel->setInstanceParameters($instanceParams[$identifier]);
} else {
  $kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
}

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
