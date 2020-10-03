<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\Debug;

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
$loader = require __DIR__.'/../app/autoload.php';
Debug::enable();

$request = Request::createFromGlobals();

Request::setTrustedProxies(
  // trust *all* requests
  ['10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16'],
  // if you're using ELB, otherwise use a constant from above
  Request::HEADER_X_FORWARDED_ALL
);

$identifier = '';

// Todo: find better way
if ($request->server->has('PATH_INFO')) {
    $pathArray = explode('/',$request->server->get('PATH_INFO'));
    $identifier = $pathArray[1];
} elseif ($request->server->has('REQUEST_URI')) {
    $pathArray = explode('/',$request->server->get('REQUEST_URI'));
    $identifier = $pathArray[1];
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

if ( !empty($identifier) && file_exists( __DIR__.'/../app/config/' .$identifier ) ) {
    $kernel = new InstanceKernel($env, $debug);
    $kernel->setIdentifier($identifier);
} else {
    $kernel = new AppKernel($env, $debug);
}

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
