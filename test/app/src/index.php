<?php


use Tier\Executable;
use Tier\HTTPFunction;
use Tier\Tier;
use Tier\TierHTTPApp;
use Room11\HTTP\Request\CLIRequest;

ini_set('display_errors', 'on');

$autoloader = require __DIR__.'/../../../vendor/autoload.php';

HTTPFunction::setupErrorHandlers();

ini_set('display_errors', 'off');

// Read application config params
$injectionParams = require_once "injectionParams.php";

// Contains helper functions for the application.
require_once "appFunctions.php";
require_once "routes.php";

if (strcasecmp(PHP_SAPI, 'cli') === 0) {
    $request = new CLIRequest('/cleanupException', 'example.com');
}
else {
    $request = HTTPFunction::createRequestFromGlobals();
}

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

$app->addSendExecutable(['Tier\HTTPFunction', 'sendBodyResponse']);

$app->createStandardExceptionResolver();

// Run it
$app->execute($request);
