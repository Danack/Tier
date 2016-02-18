<?php

namespace Tier;

use Auryn\InjectorException;
use Auryn\InjectionException;
use Jig\JigException;
use Psr\Http\Message\ServerRequestInterface as Request;
use Room11\HTTP\Body;
use Room11\HTTP\Body\HtmlBody;
use Room11\HTTP\HeadersSet;
use Tier\Body\ExceptionHtmlBody;
use Zend\Diactoros\Response\EmitterInterface;
use Zend\Diactoros\ServerRequestFactory;

/**
 * Class HTTPFunction
 * Set of utility functions for HTTP applications.
 */
class HTTPFunction
{
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

    public static function tierErrorHandler($errorNumber, $errorMessage, $errorFile, $errorLine)
    {
        if (error_reporting() === 0) {
            // Error reporting has be silenced
            return true;
        }
        if ($errorNumber === E_DEPRECATED) {
            return true; //Don't care - deprecated warnings are generally not useful
        }

        if ($errorNumber === E_CORE_ERROR || $errorNumber === E_ERROR) {
            // For these two types, PHP is shutting down anyway. Return false
            // to allow shutdown to continue
            return false;
        }

        $message = "Error: [$errorNumber] $errorMessage in file $errorFile on line $errorLine<br />\n";
        throw new \Exception($message);
    }

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

    public static function tierExceptionHandler($ex)
    {
        $body = new ExceptionHtmlBody(TierFunction::getExceptionString($ex), 500);
        self::sendRawBodyResponse($body);
    }

    public static function setupErrorHandlers()
    {
        $initialOBLevel = ob_get_level();
        $shutdownFunction = function () use ($initialOBLevel) {
            while (ob_get_level() > $initialOBLevel) {
                ob_end_clean();
            }
            self::tierShutdownFunction();
        };
        register_shutdown_function($shutdownFunction);
        set_exception_handler(['Tier\HTTPFunction', 'tierExceptionHandler']);
        set_error_handler(['Tier\HTTPFunction', 'tierErrorHandler']);
    }

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
    }

    /**
     * @param Body $body
     * @param Request $request
     * @param HeadersSet $headerSet
     * @param EmitterInterface $emitterInterface
     * @internal param Response $response
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
        return \Tier\TierApp::PROCESS_CONTINUE;
    }
    
    
        /**
     * @param InjectorException $ie
     * @return int
     * @return int
     * @internal param Request $request
     */
    public static function processInjectorException(InjectorException $ie)
    {
        $exceptionString = TierFunction::getExceptionString($ie);
        $body = new ExceptionHtmlBody($exceptionString, 500);
        self::sendRawBodyResponse($body);

        return \Tier\TierApp::PROCESS_END;
    }

    /**
     * @param \Exception $e
     * @return int
     * @return int
     */
    public static function processException(\Exception $e)
    {
        $exceptionString = TierFunction::getExceptionString($e);
        $body = new ExceptionHtmlBody($exceptionString, 500);
        self::sendRawBodyResponse($body);

        return \Tier\TierApp::PROCESS_END;
    }

    /**
     * @param JigException $je
     * @return int
     * @return int
     */
    public static function processJigException(JigException $je)
    {
        $exceptionString = TierFunction::getExceptionString($je);
        $body = new ExceptionHtmlBody($exceptionString, 500);
        self::sendRawBodyResponse($body);
    }

    /**
     * @param InjectionException $ie
     * @return int
     */
    public static function processInjectionException(InjectionException $ie)
    {
        $body = $ie->getMessage()."\n\n";
        $body .= "Dependency chain is:\n\n";
        $body .= implode("\n", $ie->getDependencyChain());
        $body .= "Stack trace:\n";
        $body .= TierFunction::getExceptionString($ie);
        $body = new ExceptionHtmlBody($body, 500);
        self::sendRawBodyResponse($body);
    }

    /**
     * @param \Exception $e
     * @return int
     * @return int
     */
    public static function processThrowable(\Throwable $e)
    {
        $exceptionString = TierFunction::getExceptionString($e);
        $body = new ExceptionHtmlBody($exceptionString, 500);
        self::sendRawBodyResponse($body);
    }
}
