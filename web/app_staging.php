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

// Modifico cf per testare imis, vecchio cf: RLDLCU77T05G224

/**
$_SERVER += [
    "REDIRECT_shibb_pat_attribute_codicefiscale" => "CMRDRS68P42Z112Q",
    "REDIRECT_shibb_pat_attribute_cognome" => "Realdi",
    "REDIRECT_shibb_pat_attribute_emailaddress" => "lr@opencontent.it",
    "REDIRECT_shibb_pat_attribute_nome" => "Luca",
    "REDIRECT_shibb_pat_attribute_telefono" => "1234567890",
    "REDIRECT_shibb_pat_attribute_indirizzoresidenza" => "Via Monte Pertica 25",
    "REDIRECT_shibb_pat_attribute_capresidenza" => "38100",
    "REDIRECT_shibb_pat_attribute_cittaresidenza" => "Trento",
    "REDIRECT_shibb_pat_attribute_provinciaresidenza" => "Trento",
    "REDIRECT_shibb_pat_attribute_statoresidenza" => "Italia",
    "REDIRECT_shibb_pat_attribute_x509certificate_issuerdn" => "FAKE_issuerdn",
    "REDIRECT_shibb_pat_attribute_x509certificate_subjectdn" => "FAKE_subjectdn",
    "REDIRECT_shibb_pat_attribute_x509certificate_base64" => "DQpSZXN1bHQgZ29lcyBoZXJlLi4uDQpCYXNlNjQNCg0KQmFzZTY0IGlzIGEgZ2VuZXJpYyB0ZXJtIGZvciBhIG51bWJlciBvZiBzaW1pbGFyIGVuY29kaW5nIHNjaGVtZXMgdGhhdCBlbmNvZGUgYmluYXJ5IGRhdGEgYnkgdHJlYXRpbmcgaXQgbnVtZXJpY2FsbHkgYW5kIHRyYW5zbGF0aW5nIGl0IGludG8gYSBiYXNlIDY0IHJlcHJlc2VudGF0aW9uLiBUaGUgQmFzZTY0IHRlcm0gb3JpZ2luYXRlcyBmcm9tIGEgc3BlY2lmaWMgTUlNRSBjb250ZW50IHRyYW5zZmVyIGVuY29kaW5nLg==",
];
*/

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
