<?php


use Tier\Executable;
use Tier\Tier;
use Tier\TierHTTPApp;

ini_set('display_errors', 'on');

$autoloader = require __DIR__.'/../../../vendor/autoload.php';

Tier::setupErrorHandlers();

ini_set('display_errors', 'off');

// Read application config params
$injectionParams = require_once "injectionParams.php";

// Contains helper functions for the application.
require_once "appFunctions.php";
require_once "routes.php";


$request = Tier::createRequestFromGlobals();

// Create the first Tier that needs to be run.
$routingExecutable = new Executable(
    ['Tier\JigBridge\Router', 'routeRequest'],
    null,
    null,
    'Room11\HTTP\Body' //skip if this has already been produced
);


// Create the Tier application
$app = new TierHTTPApp($injectionParams);

// Make the body that is generated be shared by TierApp
$app->addExpectedProduct('Room11\HTTP\Body');


$app->addGenerateBodyExecutable($routingExecutable);

$app->addSendExecutable(['Tier\Tier', 'sendBodyResponse']);

$app->createStandardExceptionResolver();

// Run it
$app->execute($request);
