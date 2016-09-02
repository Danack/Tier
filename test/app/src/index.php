<?php

use Auryn\Injector;
use Room11\HTTP\Body\TextBody;
use Tier\Executable;
use Tier\HTTPFunction;
use Tier\TierFunction;
use Tier\TierHTTPApp;
use Room11\HTTP\Request\CLIRequest;
use Tier\Exception\RouteNotMatchedException;
use Tier\Exception\MethodNotAllowedException;

ini_set('display_errors', 'on');

$autoloader = require __DIR__.'/../../../vendor/autoload.php';

set_error_handler(['Tier\HTTPFunction', 'tierErrorHandler']);

HTTPFunction::setupShutdownFunction();

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
    ['Tier\Bridge\FastRouter', 'routeRequest'],
    null,
    null,
    'Room11\HTTP\Body' //skip if this has already been produced
);

// Create the Tier application
$injector = new Injector();

/** @var $injectionParams \Tier\InjectionParams */
$injectionParams->addToInjector($injector);

$app = new TierHTTPApp($injector);

// Make the body that is generated be shared by TierApp
$app->addExpectedProduct('Room11\HTTP\Body');


define('TIER_ROUTING', 10);
define('TIER_GENERATE_RESPONSE', 10);
define('TIER_BEFORE_SEND', 80);
define('TIER_SEND', 90);

$app->addExecutable(TIER_GENERATE_RESPONSE, $routingExecutable);

$app->addExecutable(TIER_SEND, ['Tier\HTTPFunction', 'sendBodyResponse']);


try {
    // Run it
    $app->execute($request);
}
catch (\Exception $e) {
    $body = new TextBody("Exception: '" . $e->getMessage() . "'", 500);
    HTTPFunction::sendRawBodyResponse($body);
}
