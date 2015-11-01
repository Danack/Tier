<?php

use Tier\InjectionParams;

// These classes will only be created once by the injector.
$shares = [
    'Jig\JigRender',
    'Jig\Jig',
    'Jig\JigConverter',
    'Tier\Data\PDOSQLConfig',
    '\PDO',
    'Amp\Reactor',
];


// Alias interfaces (or classes) to the actual types that should be used 
// where they are required. 
$aliases = [
    'ArtaxServiceBuilder\ResponseCache' =>
    'ArtaxServiceBuilder\ResponseCache\NullResponseCache',
];

// Delegate the creation of types to callables.
$delegates = [
    'Amp\Reactor' => 'Amp\getReactor',
    'GithubService\GithubArtaxService\GithubService' => 'createGithubArtaxService',
    'Jig\JigConfig' => 'createJigConfig',
    '\PDO' => 'createPDO',
    'Tier\Data\PDOSQLConfig' => 'createPDOSQLConfig',
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