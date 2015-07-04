<?php

use Tier\Tier;

use Arya\Response;
use Arya\Body as ResponseBody;
use Auryn\InjectorException;

use Tier\ResponseBody\ExceptionHtmlBody;

require_once "../src/bootstrap.php";

$injector = bootstrapInjector();

$request = generateRequest();
$injector->share($request);

$response = new Response;
$injector->share($response);

$callable = 'getRouteCallable';

$obj = $injector->make('GithubService\GithubArtaxService\GithubService');


try {
    $count = 0;
    $responseBody = null;

    do {
        $result = $injector->execute($callable);
    
        if ($result instanceof ResponseBody) {
            $responseBody = $result;
            break;
        }
        else if ($result instanceof Tier) {
            addInjectionParams($injector, $result);
            $callable = $result->getCallable();
        }
        else if ($result === null) {
            throw new \Exception("Return value of tier must be either a response or a tier, null given.");
        }
        else {
            throw new \Exception("Return value of tier must be either a response or a tier, instead ".get_class($result).' returned.');
        }
        
        $count++;
    } while ($count < 10);
    
    if ($responseBody) {
        $response->setBody($responseBody);
        sendResponse($request, $response);
    }
}
catch (InjectorException $ie) {
    $body = new ExceptionHtmlBody($je);
    sendErrorResponse($request, $body, 500);
}
catch(Jig\JigException $je) {
    $body = new ExceptionHtmlBody($je);
    sendErrorResponse($request, $body, 500);
}
catch(\Exception $e) {
    $body = new ExceptionHtmlBody($e);
    sendErrorResponse($request, $body, 500);
}



        
        
