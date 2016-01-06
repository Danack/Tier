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
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Tier\Body\ExceptionHtmlBody;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response\EmitterInterface;

if (class_exists('Throwable') === false) {
    require __DIR__."/Throwable.php";
}

class Tier
{
    /**
     * @return \Psr\Http\Message\RequestInterface
     */
    static function createRequestFromGlobals()
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
        catch (\Exception $e) {
            // Exit quickly. Something is seriously wrong and we will not be able to
            // handle it inside the application.
            header("Server unavailable", true, 501);
            echo "Failed to read globals to create request. ".$e->getMessage();
            exit(0);
        }
    }


    static function tierErrorHandler($errno, $errstr, $errfile, $errline)
    {
        if (error_reporting() == 0) {
            return true;
        }
        if ($errno == E_DEPRECATED) {
            return true; //Don't care - deprecated warnings are generally not useful
        }

        if ($errno == E_CORE_ERROR || $errno == E_ERROR) {
            return false;
        }

        $message = "Error: [$errno] $errstr in file $errfile on line $errline<br />\n";
        throw new \Exception($message);
    }


    static function tierShutdownFunction()
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
            $msg = "<pre style='$preStyle'>{$msg}</pre>";

            echo "<html><body><h1>500 Internal Server Error</h1><hr/>{$msg}</body></html>";
        }
    }

    /**
     * Helper function to allow template rendering to be easier.
     * @param $templateName
     * @param array $sharedObjects
     * @return Executable
     */
    static function getRenderTemplateTier($templateName, array $sharedObjects = [])
    {
        $fn = function (Jig $jigRender) use ($templateName, $sharedObjects) {
            $className = $jigRender->compile($templateName);

            $alias = [];
            $alias['Jig\JigBase'] = $className;
            $injectionParams = new InjectionParams($sharedObjects, $alias, [], []);

            return new Executable('Tier\createHtmlBody', $injectionParams);
        };

        return new Executable($fn);
    }


    /**
     * @param JigBase $template
     * @return HtmlBody
     * @throws \Exception
     * @throws \Jig\JigException
     */
    static function createHtmlBody(JigBase $template)
    {
        $text = $template->render();

        return new HtmlBody($text);
    }

    static function tierExceptionHandler(\Exception $ex)
    {
        //TODO - need to ob_end_clean as many times as required because
        //otherwise partial content gets sent to the client.

        if (headers_sent() == false) {
            header("HTTP/1.0 500 Internal Server Error", true, 500);
        }
        else {
            //Exception after headers sent
        }
        
        var_dump($ex);

        echo self::getExceptionString($ex);
    }

    static function getExceptionString(\Exception $ex)
    {
        $string = '';

        while ($ex) {
            $number = 0;
            $string .= "Exception ".get_class($ex).": '".$ex->getMessage()."'\n\n";

            foreach ($ex->getTrace() as $tracePart) {
                $line = false;
                if (isset($tracePart['file']) && isset($tracePart['line'])) {
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
                $string .= "\nPrevious ";
            }
        };

        return $string;
    }


    static function setupErrorHandlers()
    {
        register_shutdown_function(['Tier\Tier', 'tierShutdownFunction']);
        set_exception_handler(['Tier\Tier', 'tierExceptionHandler']);
        set_error_handler(['Tier\Tier', 'tierErrorHandler']);
    }


    /**
     * @param JigException $je
     * @param Request $request
     */
    static function processJigException(
        JigException $je,
        Request $request,
        EmitterInterface $emitter
    ) {
        $exceptionString = Tier::getExceptionString($je);
        $body = new ExceptionHtmlBody($exceptionString, 500);
        $headersSet = new HeadersSet();
        $response = new TierResponse($request, $headersSet, $body);
        $emitter->emit($response);

        return \Tier\TierApp::PROCESS_END;
    }

    /**
     * @param InjectionException $ie
     * @param \Psr\Http\Message\RequestInterface $request
     */
    static function processInjectionException(
        InjectionException $ie,
        Request $request,
        EmitterInterface $emitter
    ) {
        $body = $ie->getMessage()."\n\n";
        $body .= "Dependency chain is:\n\n";
        $body .= implode("\n", $ie->getDependencyChain());
        $body = new ExceptionHtmlBody($body, 500);

        $headersSet = new HeadersSet();
        $response = new TierResponse($request, $headersSet, $body);
        $emitter->emit($response);

        return \Tier\TierApp::PROCESS_END;
    }

    /**
     * @param InjectorException $ie
     * @param Request $request
     */
    static function processInjectorException(
        InjectorException $ie,
        Request $request,
        EmitterInterface $emitter
    ) {
        $exceptionString = Tier::getExceptionString($ie);
        $body = new ExceptionHtmlBody($exceptionString, 500);
        $headersSet = new HeadersSet();
        $response = new TierResponse($request, $headersSet, $body);
        $emitter->emit($response);

        return \Tier\TierApp::PROCESS_END;
    }

    /**
     * @param \Exception $e
     * @param Request $request
     */
    static function processException(
        \Exception $e,
        Request $request,
        EmitterInterface $emitter
    ) {
        $exceptionString = Tier::getExceptionString($e);
        $body = new ExceptionHtmlBody($exceptionString, 500);
        $headersSet = new HeadersSet();
        $response = new TierResponse($request, $headersSet, $body);
        $emitter->emit($response);

        return \Tier\TierApp::PROCESS_END;
    }

    
    /**
     * @param Body $body
     * @param Request $request
     * @param Response $response
     * @param HeadersSet $headerSet
     * @return int
     * @throws TierException
     */
    static function sendBodyResponse(
        Body $body,
        Request $request,
        HeadersSet $headerSet,
        EmitterInterface $emitterInterface
    ) {
        $response = new TierResponse($request, $headerSet, $body);
        
        $emitterInterface->emit($response);
        return \Tier\TierApp::PROCESS_END;
    }
}