<?php

use Arya\Request;
use Auryn\InjectorException;
use Jig\JigException;
use Tier\Tier;
use Tier\TierApp;
use Tier\ResponseBody\ExceptionHtmlBody;

$autoloader = require_once realpath(__DIR__).'/../vendor/autoload.php';
$autoloader->add('Jig', [realpath(__DIR__).'/../var/compile/']);
$injectionParams = require_once "injectionParams.php";
require_once "appFunctions.php";
require_once "../lib/Tier/tierFunctions.php";

try {
    $_input = empty($_SERVER['CONTENT-LENGTH']) ? NULL : fopen('php://input', 'r');
    $request = new Request($_SERVER, $_GET, $_POST, $_FILES, $_COOKIE, $_input);
    $tier = new Tier('getRouteCallable', $injectionParams);
    $app = new TierApp($tier);
    $app->execute($request);
}
catch (InjectorException $ie) {
    // TODO - add custom notifications.
    $body = new ExceptionHtmlBody($ie);
    \Tier\sendErrorResponse($request, $body, 500);
}
catch (JigException $je) {
    $body = new ExceptionHtmlBody($je);
    \Tier\sendErrorResponse($request, $body, 500);
}
catch (\Exception $e) {
    $body = new ExceptionHtmlBody($e);
    \Tier\sendErrorResponse($request, $body, 500);
}
