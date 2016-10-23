<?php

namespace Tier;

use Psr\Http\Message\ServerRequestInterface as Request;
use Room11\HTTP\Body;
use Room11\HTTP\HeadersSet;
use Zend\Diactoros\Response\EmitterInterface;
use Zend\Diactoros\ServerRequestFactory;
use Room11\HTTP\Body\HtmlBody;
use Room11\HTTP\Body\ExceptionHtmlBody;

/**
 * Class HTTPFunction
 * Set of utility functions for HTTP applications.
 */
class HTTPFunction
{
    /**
     * @param Body $body
     * @param Request $request
     * @param HeadersSet $headerSet
     * @param EmitterInterface $emitterInterface
     * @return int
     */
    public static function sendBodyResponse(
        Body $body,
        Request $request,
        HeadersSet $headerSet,
        EmitterInterface $emitterInterface
    ) {
        $response = new TierResponse($request, $headerSet, $body);

        $emitterInterface->emit($response);
        flush();

        return \Tier\TierApp::PROCESS_CONTINUE;
    }

    /**
     * @param Body $body
     */
    public static function sendRawBodyResponse(Body $body)
    {
        if (headers_sent() === false) {
            header_remove();
            $message = sprintf(
                "HTTP/1.0 %d %s",
                $body->getStatusCode(),
                $body->getReasonPhrase()
            );
            header($message, true, $body->getStatusCode());
        }

        $body->sendData();
        flush();
    }


    /**
     * @return \Psr\Http\Message\RequestInterface
     */
    public static function createRequestFromGlobals()
    {
        try {
            $request = ServerRequestFactory::fromGlobals(
                $_SERVER,
                $_GET,
                $_POST,
                $_COOKIE,
                $_FILES
            );

            return $request;
        }
        catch (\Throwable $e) {
            // Exit quickly. Something is seriously wrong and we will not be able to
            // handle it inside the application.
            header("Server unavailable", true, 501);
            echo "Failed to read globals to create request. ".$e->getMessage();
            exit(0);
        }
        catch (\Exception $e) {
            // Exit quickly. Something is seriously wrong and we will not be able to
            // handle it inside the application.
            header("Server unavailable", true, 501);
            echo "Failed to read globals to create request. ".$e->getMessage();
            exit(0);
        }
    }

    /**
     * This fatal error shutdown handler will only get called when there is a serious fatal
     * error with your application.
     */
    public static function tierShutdownFunction()
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

        if (empty($lastError) !== true && in_array($lastError['type'], $fatals) === true) {
            if (headers_sent() === true) {
                return;
            }
            extract($lastError);
            $msg = sprintf("Fatal error: %s in %s on line %d", $message, $file, $line);

            $preStyles = [
                "color: red",
                "white-space: pre-wrap",       /* css-3 */
                "white-space: -moz-pre-wrap",  /* Mozilla, since 1999 */
                "white-space: -pre-wrap",      /* Opera 4-6 */
                "white-space: -o-pre-wrap",    /* Opera 7 */
                "word-wrap: break-word",       /* Internet Explorer 5.5+ */
            ];

            $preStyle = implode(";", $preStyles);

            $html = <<< HTML
<html>
  <body>
    <h1>500 Internal Server Error</h1>
    <hr/>
    <pre style='%s'>%s</pre>
  </body>
</html>
HTML;

            $output = sprintf(
                $html,
                $preStyle,
                $msg
            );

            $body = new HtmlBody($output, 500);
            self::sendRawBodyResponse($body);
        }
    }



    /**
     * Converts any non-suppressed warning or error to an exception.
     * @param $errorNumber
     * @param $errorMessage
     * @param $errorFile
     * @param $errorLine
     * @return bool
     * @throws \Exception
     */
    public static function tierErrorHandler($errorNumber, $errorMessage, $errorFile, $errorLine)
    {
        if (error_reporting() === 0) {
            // Error reporting has be silenced
            return true;
        }
        if ($errorNumber === E_DEPRECATED) {
            return false;
        }

        if ($errorNumber === E_CORE_ERROR || $errorNumber === E_ERROR) {
            // For these two types, PHP is shutting down anyway. Return false
            // to allow shutdown to continue
            return false;
        }

        $message = "Error: [$errorNumber] $errorMessage in file $errorFile on line $errorLine<br />\n";
        throw new \Exception($message);
    }

    /**
     *
     */
    public static function setupShutdownFunction()
    {
        $initialOBLevel = ob_get_level();
        $shutdownFunction = function () use ($initialOBLevel) {
            while (ob_get_level() > $initialOBLevel) {
                ob_end_clean();
            }
            self::tierShutdownFunction();
        };
        register_shutdown_function($shutdownFunction);
    }

//    /*
//    public static function setupErrorHandlers()
//    {
//        set_error_handler(['Tier\HTTPFunction', 'tierErrorHandler']);
//    }
}
