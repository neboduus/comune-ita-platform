<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\Debug;

// If you don't want to setup permissions the proper way, just uncomment the following PHP line
// read http://symfony.com/doc/current/book/installation.html#checking-symfony-application-configuration-and-setup
// for more information
//umask(0000);

// This check prevents access to debug front controllers that are deployed by accident to production servers.
// Feel free to remove this, extend it, or make something more sophisticated.
if (isset($_SERVER['HTTP_CLIENT_IP'])
    || isset($_SERVER['HTTP_X_FORWARDED_FOR'])
    || !(in_array(@$_SERVER['REMOTE_ADDR'], ['127.0.0.1', 'fe80::1', '::1']) || php_sapi_name() === 'cli-server')
) {
    header('HTTP/1.0 403 Forbidden');
    exit('You are not allowed to access this file. Check '.basename(__FILE__).' for more information.');
}

/**
 * @var Composer\Autoload\ClassLoader $loader
 */
$loader = require __DIR__.'/../app/autoload.php';
Debug::enable();

$_SERVER += [
    "shibb_pat_attribute_codicefiscale" => "RLDLCU77T05G224F",
    "shibb_pat_attribute_cognome" => "Realdi",
    "shibb_pat_attribute_emailaddress" => "lr@opencontent.it",
    "shibb_pat_attribute_nome" => "Luca",
    "shibb_pat_attribute_telefono" => "123",
    "shibb_pat_attribute_cellulare" => "456",
    "shibb_pat_attribute_indirizzoresidenza" => "Via il male dal mondo, 15",
    "shibb_pat_attribute_capresidenza" => "00100",
    "shibb_pat_attribute_cittaresidenza" => "Roma",
    "shibb_pat_attribute_provinciaresidenza" => "Roma",
    "shibb_pat_attribute_statoresidenza" => "Tristalia",
    "shibb_pat_attribute_x509certificate_issuerdn" => "FAKE_issuerdn",
    "shibb_pat_attribute_x509certificate_subjectdn" => "FAKE_subjectdn",
    "shibb_pat_attribute_x509certificate_base64" => "DQpSZXN1bHQgZ29lcyBoZXJlLi4uDQpCYXNlNjQNCg0KQmFzZTY0IGlzIGEgZ2VuZXJpYyB0ZXJtIGZvciBhIG51bWJlciBvZiBzaW1pbGFyIGVuY29kaW5nIHNjaGVtZXMgdGhhdCBlbmNvZGUgYmluYXJ5IGRhdGEgYnkgdHJlYXRpbmcgaXQgbnVtZXJpY2FsbHkgYW5kIHRyYW5zbGF0aW5nIGl0IGludG8gYSBiYXNlIDY0IHJlcHJlc2VudGF0aW9uLiBUaGUgQmFzZTY0IHRlcm0gb3JpZ2luYXRlcyBmcm9tIGEgc3BlY2lmaWMgTUlNRSBjb250ZW50IHRyYW5zZmVyIGVuY29kaW5nLg==",
];

$request = Request::createFromGlobals();
//\Symfony\Component\VarDumper\VarDumper::dump($request);

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
    $kernel = new InstanceKernel('dev', true);
    $kernel->setIdentifier($identifier);
}
else
{
    $kernel = new AppKernel('dev', true);
}

$kernel->loadClassCache();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
