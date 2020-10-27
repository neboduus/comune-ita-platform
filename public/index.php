<?php

use App\InstancesProvider;
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

$instanceProvider = InstancesProvider::factory();

$request = Request::createFromGlobals();
$host = $request->getHost();
$pathInfoParts = explode('/', trim($request->getPathInfo(), '/'));
$path = isset($pathInfoParts[0]) ? $pathInfoParts[0] : null;

$instanceIdentifier = $instanceProvider->match($host, $path);

if ($instanceIdentifier instanceof RedirectResponse){
  $instanceIdentifier->send();
  exit;
}

if ($instanceIdentifier && $instanceProvider->hasInstance($instanceIdentifier)) {
  $kernel = new InstanceKernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
  $kernel->setIdentifier($instanceIdentifier);
  $kernel->setInstanceParameters($instanceProvider->getInstance($instanceIdentifier));
} else {
  $kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
}

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
