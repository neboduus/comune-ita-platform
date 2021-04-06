<?php

use QueueIT\KnownUserV3\SDK\KnownUser;
use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;

// If you don't want to setup permissions the proper way, just uncomment the following PHP line
// read http://symfony.com/doc/current/book/installation.html#checking-symfony-application-configuration-and-setup
// for more information
//umask(0000);

// This check prevents access to debug front controllers that are deployed by accident to production servers.
// Feel free to remove this, extend it, or make something more sophisticated.
/*if (isset($_SERVER['HTTP_CLIENT_IP'])
    || isset($_SERVER['HTTP_X_FORWARDED_FOR'])
    || !(in_array(@$_SERVER['REMOTE_ADDR'], ['127.0.0.1', 'fe80::1', '::1']) || php_sapi_name() === 'cli-server')
) {
    header('HTTP/1.0 403 Forbidden');
    exit('You are not allowed to access this file. Check '.basename(__FILE__).' for more information.');
}*/

/**
 * @var Composer\Autoload\ClassLoader $loader
 */
$loader = require __DIR__ . '/../app/autoload.php';
Debug::enable();

$request = Request::createFromGlobals();

Request::setTrustedProxies(
// trust *all* requests
  ['10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16'],
  // if you're using ELB, otherwise use a constant from above
  Request::HEADER_X_FORWARDED_ALL
);

// handle queue-it if needed as soon as possible before kernel instantiation
// @see https://github.com/queueit/KnownUser.V3.PHP/blob/master/README.md
$queueItCustomerId = getenv('QUEUEIT_CUSTOMER_ID');
if ($queueItCustomerId !== false) {
  $queueItConfigFile = getenv('QUEUEIT_CONFIG_FILE');
  if (!file_exists($queueItConfigFile)) {
    error_log("[QUEUEIT] Config file not found");
  }
  $queueItConfig = @file_get_contents($queueItConfigFile);
  $queueItSecretKey = getenv('QUEUEIT_SECRET');
  $queueItToken = $request->query->get('queueittoken', '');
  $fullUrl = $request->getUri();
  $currentUrlWithoutQueueItToken = preg_replace("/([\\?&])(" . "queueittoken" . "=[^&]*)/i", "", $fullUrl);
  try {
    $response = null;
    $result = KnownUser::validateRequestByIntegrationConfig(
      $currentUrlWithoutQueueItToken, $queueItToken, $queueItConfig, $queueItCustomerId, $queueItToken
    );
    if ($result->doRedirect()) {
      if (!$result->isAjaxResult) {
        //Send the user to the queue - either because hash was missing or because is was invalid
        $response = new RedirectResponse($result->redirectUrl, Response::HTTP_FOUND);
      } else {
        $response = new Response('', Response::HTTP_OK, [
          $result->getAjaxQueueRedirectHeaderKey() => $result->getAjaxRedirectUrl()
        ]);
      }
    }
    if (!empty($queueItToken) && $result->actionType == "Queue") {
      //Request can continue - we remove queueittoken form querystring parameter to avoid sharing of user specific token
      $response = new RedirectResponse($currentUrlWithoutQueueItToken, Response::HTTP_FOUND);
    }
    if ($response instanceof Response) {
      $response->setPrivate();
      $response->setExpires(new DateTime('1977-12-05'));
      $response->headers->set('pragma', 'no-cache');
      $response->send();
      exit();
    }
  } catch (Throwable $e) {
    error_log("[QUEUEIT] Error: {$e->getMessage()} in $fullUrl request");
  }
}

// Load environment from server variables, default is prod
$env = 'prod';
$debug = false;
if ($request->server->has('SYMFONY_ENV') && in_array($request->server->get('SYMFONY_ENV'), ['prod', 'dev', 'test'])) {
  $env = $request->server->get('SYMFONY_ENV');
  if ($env == 'dev') {
    $debug = true;
  }
}

$currentInstance = false;
$instances = Yaml::parse(file_get_contents(__DIR__ . '/../app/instances_' . $env . '.yml'));
$instanceParams = $instances['instances'];


$host = $request->getHost();
$pathInfoParts = explode('/', trim($request->getPathInfo(), '/'));
$path = isset($pathInfoParts[0]) ? $pathInfoParts[0] : null;

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
} else {
  $kernel = new AppKernel($env, $debug);
}

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
