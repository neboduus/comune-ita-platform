<?php

use Symfony\Component\HttpFoundation\Request;

/**
 * @var Composer\Autoload\ClassLoader
 */
$loader = require __DIR__.'/../app/autoload.php';
include_once __DIR__.'/../var/bootstrap.php.cache';

// When using the HttpCache, you need to call the method in your front controller instead of relying on the configuration parameter
//Request::enableHttpMethodParameterOverride();
$request = Request::createFromGlobals();
$identifier = '';

// Todo: find better way
if ($request->server->has('PATH_INFO'))
{
    $pathArray = explode('/',$request->server->get('PATH_INFO'));
    $identifier = $pathArray[1];
}
elseif ($request->server->has('REQUEST_URI'))
{
    $pathArray = explode('/',$request->server->get('REQUEST_URI'));
    $identifier = $pathArray[1];
}

if ( !empty($identifier) && file_exists( __DIR__.'/../app/config/' .$identifier ) )
{
    $kernel = new InstanceKernel('prod', true);
    $kernel->setIdentifier($identifier);
}
else
{
    $kernel = new AppKernel('prod', true);
}
$kernel->loadClassCache();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
