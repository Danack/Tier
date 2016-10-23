<?php

use AurynConfig\InjectionParams;

// These classes will only be created  by the injector once
$shares = [
    'Jig\Jig',
    'Jig\JigConverter',
    'App\Config',
    new \Jig\JigTemplatePath(__DIR__."/../../fixtures/templates/"),
    new \Jig\JigCompilePath(__DIR__."/../../var/generatedTemplates/"),
];
    

// Alias interfaces (or classes) to the actual types that should be used 
// where they are required. 
$aliases = [
    'Jig\Escaper' => 'Jig\Bridge\ZendEscaperBridge',
    'Room11\HTTP\RequestHeaders' => 'Room11\HTTP\RequestHeaders\HTTPRequestHeaders',
    'Room11\HTTP\RequestRouting' => 'Room11\HTTP\RequestRouting\PSR7RequestRouting',
    'Room11\HTTP\VariableMap' => 'Room11\HTTP\VariableMap\PSR7VariableMap',
    'Zend\Diactoros\Response\EmitterInterface' => 'Zend\Diactoros\Response\SapiEmitter',
];

// Delegate the creation of types to callables.
$delegates = [
    'Jig\JigConfig' => 'createJigConfig',
    //'Room11\Caching\LastModifiedStrategy' => 'createCaching',
    'FastRoute\Dispatcher' => 'createDispatcher',
];

// If necessary, define some params that can be injected purely by name.
$params = [ ];

$defines = [

];

$prepares = [
    //'Jig\Jig' => 'prepareJig'
];

$injectionParams = new InjectionParams(
    $shares,
    $aliases,
    $delegates,
    $params,
    $prepares,
    $defines
);

return $injectionParams;
