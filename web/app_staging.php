<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\Debug;

/**
 * Staging front controller, same as the dev one, but without checks
 * To be removed from prod deployment together with the dev one
 */

/**
 * @var Composer\Autoload\ClassLoader $loader
 */
$loader = require __DIR__.'/../app/autoload.php';
Debug::enable();

$kernel = new AppKernel('dev', true);
$kernel->loadClassCache();

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
