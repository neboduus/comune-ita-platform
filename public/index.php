<?php

use App\InstanceKernel;
use App\Kernel;
use Symfony\Component\ErrorHandler\Debug;
use QueueIT\KnownUserV3\SDK\KnownUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;

require dirname(__DIR__).'/config/bootstrap.php';

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
    Debug::enable();
}

if ($trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? false) {
    Request::setTrustedProxies(explode(',', $trustedProxies), Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO);
}

if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? false) {
    Request::setTrustedHosts([$trustedHosts]);
}

$request = Request::createFromGlobals();

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
      $currentUrlWithoutQueueItToken, $queueItToken, $queueItConfig, $queueItCustomerId, $queueItSecretKey
    );
    if ($result->actionType) {
      error_log("[QUEUEIT] Result $result->actionName $result->actionType in $fullUrl request");
    }
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
if ($request->server->has('APP_ENV') && in_array($request->server->get('APP_ENV'), ['prod', 'dev', 'test'])) {
  $env = $request->server->get('APP_ENV');
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
  $kernel = new Kernel($env, $debug);
}

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
