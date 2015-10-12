<?php


use Tier\Tier;
use Tier\TierApp;

$autoloader = require_once realpath(__DIR__).'/../vendor/autoload.php';


$autoloader->add('Tier', [__DIR__.'/']);
// Contains helper functions for the 'framework'.
require __DIR__ . "/../src/Tier/tierFunctions.php";

//// We need to add the path Jig templates are compiled into to 
//// allow them to be autoloaded
//$autoloader->add('Jig', [realpath(__DIR__).'/../var/compile/']);
//$autoloader->add('Blog', [realpath(__DIR__).'/../var/compile/']);

// Contains helper functions for the application.
require_once "appFunctions.php";

\Tier\setupErrorHandlers();

// Read application config params
$injectionParams = require_once "injectionParams.php";

$request = \Tier\createRequestFromGlobals();

// Create the first Tier that needs to be run.
$tier = new Tier('routeRequest');

// Create the Tier application
$app = new TierApp($tier, $injectionParams);
$app->setStandardExceptionHandlers();

// Run it
$app->execute($request);
