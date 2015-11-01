<?php

namespace Tier;

use Auryn\InjectionException;
use Auryn\InjectorException;
use Jig\Jig;
use Jig\JigBase;
use Jig\JigException;
use Room11\HTTP\Body;
use Room11\HTTP\Body\HtmlBody;
use Room11\HTTP\HeadersSet;
use Room11\HTTP\Request;
use Room11\HTTP\Request\Request as RequestImpl;
use Room11\HTTP\Response;
use Tier\ResponseBody\ExceptionHtmlBody;

/**
 * @return Request
 */
function createRequestFromGlobals()
{
    try {
        $_input = empty($_SERVER['CONTENT-LENGTH']) ? null : fopen('php://input', 'r');
        $request = new RequestImpl($_SERVER, $_GET, $_POST, $_FILES, $_COOKIE, $_input);
    }
    catch (\Exception $e) {
        //TODO - exit quickly.
        header("We totally failed", true, 501);
        echo "Failed to read globals to create request. ".$e->getMessage();
        exit(0);
    }

    return $request;
}


/**
 *
 */
function tierErrorHandler($errno, $errstr, $errfile, $errline)
{
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
}


/**
 * Parse errors cannot be handled inside the same file where they originate.
 * For this reason we have to include the application file externally here
 * so that our shutdown function can handle E_PARSE.
 */
function tierShutdownFunction()
{
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

        //if (DEBUG) {
        extract($lastError);
        $msg = sprintf("Fatal error: %s in %s on line %d", $message, $file, $line);
//        } else {
//            $msg = "Oops! Something went terribly wrong :(";
//        }

        $preStyles = [
            "color: red",
            "white-space: pre-wrap",       /* css-3 */
            "white-space: -moz-pre-wrap",  /* Mozilla, since 1999 */
            "white-space: -pre-wrap",      /* Opera 4-6 */
            "white-space: -o-pre-wrap",    /* Opera 7 */
            "word-wrap: break-word",       /* Internet Explorer 5.5+ */
        ];

        $preStyle = implode(";", $preStyles);
        $msg = "<pre style='$preStyle'>{$msg}</pre>";

        echo "<html><body><h1>500 Internal Server Error</h1><hr/>{$msg}</body></html>";
    }
}


/**
 * @param Request $request
 * @param $body
 * @param $errorCode
 */
function sendErrorResponse(Request $request, $body, $errorCode)
{
    $response = new \Room11\HTTP\Response\Response();
    $response->setBody($body);
    $response->setStatus($errorCode);
    sendResponse($request, $response);
}

/**
 * @param Request $request
 * @param Response $response
 * @param bool $autoAddReason
 */
function sendResponse(
    Request $request,
    Response $response,
    $autoAddReason = true
) {

    $statusCode = $response->getStatus();
    $reason = $response->getReasonPhrase();
    if ($autoAddReason && empty($reason)) {
        $reasonConstant = "Arya\\Reason::HTTP_{$statusCode}";
        $reason = defined($reasonConstant) ? constant($reasonConstant) : '';
        $response->setReasonPhrase($reason);
    }
    
    if ($response->hasHeader('Date') == false) {
         $response->addHeader("Date", gmdate("D, d M Y H:i:s", time())." UTC");
    }

    $statusLine = sprintf("HTTP/%s %s", $request['SERVER_PROTOCOL'], $statusCode);
    if (isset($reason[0])) {
        $statusLine .= " {$reason}";
    }
    
    if (headers_sent() == false) {
        // Headers sent should only happen when their is an error after the 
        // body has been sent.
        header($statusLine);
    
        $headers = $response->getAllHeaderLines();
    
        foreach ($headers as $headerLine) {
            header($headerLine, $replace = false);
        }
    }

    flush(); // Force header output

    $body = $response->getBody();

    if (method_exists($body, '__toString')) {
        echo $body->__toString();
        return;
    }
    else if (is_string($body)) {
        echo $body;
        return;
    }
    elseif (is_callable($body)) {
        $body();
        return;
    }
    
    //this is bad.
    throw new TierException("Unknown body type.");
}

/**
 * @param $result
 * @throws TierException
 */
function throwWrongTypeException($result)
{
    if ($result === null) {
        throw new TierException('Return value of tier must be either a Room11\HTTP\Body or a tier, null given.');
    }

    if (is_object($result)) {
        $detail = "object of type '".get_class($result)."' returned.";
    }
    else {
        $detail = "variable of type '".gettype($result)."' returned.";
    }

    $message = sprintf(
        'Return value of tier must be either a Room11\HTTP\Body or a tier, instead %s returned.',
        $detail
    );

    return new TierException($message);
}



/**
 * Helper function to allow template rendering to be easier.
 * @param $templateName
 * @param array $sharedObjects
 * @return Tier
 */
function getRenderTemplateTier($templateName, array $sharedObjects = [])
{
    $fn = function (Jig $jigRender) use ($templateName, $sharedObjects) {
        $className = $jigRender->getFQCNFromTemplateName($templateName);
        $jigRender->checkTemplateCompiled($templateName);

        $alias = [];
        $alias['Jig\JigBase'] = $className;
        $injectionParams = new InjectionParams($sharedObjects, $alias, [], []);

        return new Tier('Tier\createHtmlBody', $injectionParams);
    };

    return new Tier($fn);
}


/**
 * @param JigBase $template
 * @return HtmlBody
 * @throws \Exception
 * @throws \Jig\JigException
 */
function createHtmlBody(JigBase $template)
{
    $text = $template->render();

    return new HtmlBody($text);
}

function tierExceptionHandler(\Exception $ex)
{
    //TODO - need to ob_end_clean as many times as required because
    //otherwise partial content gets sent to the client.

    if (headers_sent() == false) {
        header("HTTP/1.0 500 Internal Server Error", true, 500);
    }
    else {
        //Exception after headers sent
    }

    echo getExceptionString($ex);
}

function getExceptionString(\Exception $ex) {

    $string = '';
    
    while ($ex) {
        $number = 0;
        $string .= "Exception " . get_class($ex) . ': ' . $ex->getMessage()."\n\n";

        foreach ($ex->getTrace() as $tracePart) {
            
            $line = false;
            if (isset($tracePart['file']) && isset($tracePart['line']) ) {
                $line .= $tracePart['file']." ";
                $line .= $tracePart['line']." ";
            }
            else if (isset($tracePart['file'])) {
                $line .= $tracePart['file']." ";
            }
            else if (isset($tracePart['line'])) {
                $line .= $tracePart['line']." ";
            }
            else {
                $line .= "*** "; // Some form of internal function or CUF
            }

            if (isset($tracePart["class"])) {
                $line .= $tracePart["class"];
            }
            if (isset($tracePart["type"])) {
                $line .= $tracePart["type"];
            }
            if (isset($tracePart["function"])) {
                $line .= $tracePart["function"];
            }

            $string .= sprintf("#%s %s\n", $number, $line);
            $number++;
        }
        $ex = $ex->getPrevious();
        if ($ex) {
            $string .=  "\nPrevious ";
        }
    };
    
    return $string;
}


function setupErrorHandlers(){
    register_shutdown_function('Tier\tierShutdownFunction');
    set_exception_handler('Tier\tierExceptionHandler');
    set_error_handler('Tier\tierErrorHandler');
}


function processJigException(JigException $je, Request $request)
{
    $exceptionString = \Tier\getExceptionString($je);
    // TODO - jig templates need to be easier to debug.
    $body = new ExceptionHtmlBody($exceptionString);
    \Tier\sendErrorResponse($request, $body, 500);
}

function processInjectionException(InjectionException $ie, Request $request)
{
    $body = $ie->getMessage();
    $body .= implode("\n", $ie->getDependencyChain());
    
    $body = new ExceptionHtmlBody($body);
    \Tier\sendErrorResponse($request, $body, 500);
}

function processInjectorException(InjectorException $ie, Request $request)
{
    $exceptionString = \Tier\getExceptionString($ie);
    $body = new ExceptionHtmlBody($exceptionString);
    \Tier\sendErrorResponse($request, $body, 500);
}


function processException(\Exception $e, Request $request)
{
    $exceptionString = \Tier\getExceptionString($e);
    $body = new ExceptionHtmlBody($exceptionString);
    \Tier\sendErrorResponse($request, $body, 500);
}


function sendBodyResponse(
    Body $body,
    Request $request,
    Response $response,
    HeadersSet $headerSet
) {
    $headerSet = $headerSet->getAllHeaders();
    
    foreach ($headerSet as $field => $values) {
        foreach ($values as $value) {
            $response->setHeader($field, $value);
        }
    }

    $response->setBody($body);
    \Tier\sendResponse($request, $response);

    return \Tier\TierApp::PROCESS_END;
}
