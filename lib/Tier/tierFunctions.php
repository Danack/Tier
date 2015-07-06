<?php

namespace Tier;

use ArtaxServiceBuilder\ResponseCache;
use Arya\Request;
use Arya\Response;

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (error_reporting() == 0) {
        return true;
    }
    if ($errno == E_DEPRECATED) {
        return true; //Don't care - deprecated warnings are generally not useful
    }
    
    if ($errno == E_CORE_ERROR || $errno == E_ERROR) {
        //$message = "Fatal error: [$errno] $errstr on line $errline in file $errfile <br />\n";
        return false;
    }

    $message = "Error: [$errno] $errstr in file $errfile on line $errline<br />\n";
    throw new \Exception($message);
});


/**
 * Parse errors cannot be handled inside the same file where they originate.
 * For this reason we have to include the application file externally here
 * so that our shutdown function can handle E_PARSE.
 */
register_shutdown_function(function() {
    $fatals = [
        E_ERROR,
        E_PARSE,
        E_USER_ERROR,
        E_CORE_ERROR,
        E_CORE_WARNING,
        E_COMPILE_ERROR,
        E_COMPILE_WARNING
    ];

    $lastError = error_get_last();

    if ($lastError && in_array($lastError['type'], $fatals)) {
        if (headers_sent()) {
            return;
        }

        header_remove();
        header("HTTP/1.0 500 Internal Server Error");

        define('DEBUG', true);
        
        if (DEBUG) {
            extract($lastError);
            $msg = sprintf("Fatal error: %s in %s on line %d", $message, $file, $line);
        } else {
            $msg = "Oops! Something went terribly wrong :(";
        }

        $msg = "<pre style=\"color:red;\">{$msg}</pre>";

        echo "<html><body><h1>500 Internal Server Error</h1><hr/>{$msg}</body></html>";
    }
});

function sendErrorResponse(Request $request, $body, $errorCode)
{
    $response = new Response();
    $response->setBody($body);
    $response->setStatus($errorCode);

    sendResponse($request, $response);
}

function sendResponse(Request $request, Response $response, $autoAddReason = true)
{
    $statusCode = $response->getStatus();
    $reason = $response->getReasonPhrase();
    if ($autoAddReason && empty($reason)) {
        $reasonConstant = "Arya\\Reason::HTTP_{$statusCode}";
        $reason = defined($reasonConstant) ? constant($reasonConstant) : '';
        $response->setReasonPhrase($reason);
    }

    $statusLine = sprintf("HTTP/%s %s", $request['SERVER_PROTOCOL'], $statusCode);
    if (isset($reason[0])) {
        $statusLine .= " {$reason}";
    }

    header($statusLine);

    foreach ($response->getAllHeaderLines() as $headerLine) {
        header($headerLine, $replace = FALSE);
    }

    flush(); // Force header output

    $body = $response->getBody();

    if (method_exists($body, '__toString')) {
        echo $body->__toString();
    }
    else if (is_string($body)) {
        echo $body;
    } 
    elseif (is_callable($body)) {
        $this->outputCallableBody($body);
    }
    else {
        //this is bad.
    }
}


