<?php

use Tier\InjectionParams;

// These classes will only be created once by the injector.
$shares = [
    'Jig\JigRender',
    'Jig\Jig',
    'Jig\JigConverter',
];


// Alias interfaces (or classes) to the actual types that should be used 
// where they are required. 
$aliases = [
    'Room11\HTTP\Request' => 'Room11\HTTP\Request\Request',
    'Room11\HTTP\Response' => 'Room11\HTTP\Response\Response',
];

// Delegate the creation of types to callables.
$delegates = [
    'Jig\JigConfig' => 'createJigConfig',
];

// If necessary, define some params that can be injected purely by name.
$params = [];

$injectionParams = new InjectionParams(
    $shares,
    $aliases,
    $delegates,
    $params
);

return $injectionParams;