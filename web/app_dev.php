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

$kernel = new AppKernel('dev', true);
$kernel->loadClassCache();

$_SERVER += [
    "REDIRECT_shibb_pat_attribute_codicefiscale" => "RLDLCU77T05G224",
    "REDIRECT_shibb_pat_attribute_cognome" => "Realdi",
    "REDIRECT_shibb_pat_attribute_emailaddress" => "lr@opencontent.it",
    "REDIRECT_shibb_pat_attribute_nome" => "Luca",
    "REDIRECT_shibb_pat_attribute_telefono" => "123",
    "REDIRECT_shibb_pat_attribute_cellulare" => "456",
    "REDIRECT_shibb_pat_attribute_indirizzoresidenza" => "Via il male dal mondo, 15",
    "REDIRECT_shibb_pat_attribute_capresidenza" => "00100",
    "REDIRECT_shibb_pat_attribute_cittaresidenza" => "Roma",
    "REDIRECT_shibb_pat_attribute_provinciaresidenza" => "Roma",
    "REDIRECT_shibb_pat_attribute_statoresidenza" => "Tristalia",
    "REDIRECT_shibb_pat_attribute_x509certificate_issuerdn" => "FAKE_issuerdn",
    "REDIRECT_shibb_pat_attribute_x509certificate_subjectdn" => "FAKE_subjectdn",
    "REDIRECT_shibb_pat_attribute_x509certificate_base64" => "DQpSZXN1bHQgZ29lcyBoZXJlLi4uDQpCYXNlNjQNCg0KQmFzZTY0IGlzIGEgZ2VuZXJpYyB0ZXJtIGZvciBhIG51bWJlciBvZiBzaW1pbGFyIGVuY29kaW5nIHNjaGVtZXMgdGhhdCBlbmNvZGUgYmluYXJ5IGRhdGEgYnkgdHJlYXRpbmcgaXQgbnVtZXJpY2FsbHkgYW5kIHRyYW5zbGF0aW5nIGl0IGludG8gYSBiYXNlIDY0IHJlcHJlc2VudGF0aW9uLiBUaGUgQmFzZTY0IHRlcm0gb3JpZ2luYXRlcyBmcm9tIGEgc3BlY2lmaWMgTUlNRSBjb250ZW50IHRyYW5zZmVyIGVuY29kaW5nLg==",
];

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
