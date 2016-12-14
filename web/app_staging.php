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
#Debug::enable();
include_once __DIR__.'/../var/bootstrap.php.cache';

#$kernel = new AppKernel('dev', true);
$kernel = new AppKernel('prod', true);
$kernel->loadClassCache();

$_SERVER += [
    "REDIRECT_shibb_pat_attribute_codicefiscale" => "RLDLCU77T05G224",
    "REDIRECT_shibb_pat_attribute_cognome" => "Realdi",
    "REDIRECT_shibb_pat_attribute_emailaddress" => "lr@opencontent.it",
    "REDIRECT_shibb_pat_attribute_nome" => "Luca",
    "REDIRECT_shibb_pat_attribute_telefono" => "1234567890",
    "REDIRECT_shibb_pat_attribute_indirizzoresidenza" => "Via Monte Pertica 25",
    "REDIRECT_shibb_pat_attribute_capresidenza" => "38100",
    "REDIRECT_shibb_pat_attribute_cittaresidenza" => "Trento",
    "REDIRECT_shibb_pat_attribute_provinciaresidenza" => "Trento",
    "REDIRECT_shibb_pat_attribute_statoresidenza" => "Italia",
];


$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
