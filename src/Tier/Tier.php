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

class Tier
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

    public static function tierErrorHandler($errno, $errstr, $errfile, $errline)
    {
        if (error_reporting() === 0) {
            return true;
        }
        if ($errno === E_DEPRECATED) {
            return true; //Don't care - deprecated warnings are generally not useful
        }

        if ($errno === E_CORE_ERROR || $errno === E_ERROR) {
            return false;
        }

        $message = "Error: [$errno] $errstr in file $errfile on line $errline<br />\n";
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
    public static function getRenderTemplateTier($templateName, array $sharedObjects = [])
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
    public static function createHtmlBody(JigBase $template)
    {
        $text = $template->render();

        return new HtmlBody($text);
    }

    public static function tierExceptionHandler(\Exception $ex)
    {
        //TODO - need to ob_end_clean as many times as required because
        //otherwise partial content gets sent to the client.
        $obClearCount = 0;
        while (ob_get_level() > 0 && $obClearCount < 100) {
            ob_end_clean();
            $obClearCount++;
        }

        if (headers_sent() === false) {
            header("HTTP/1.0 500 Internal Server Error", true, 500);
        }
        else {
            //Exception after headers sent
        }

        echo self::getExceptionString($ex);
    }

    public static function getExceptionString(\Exception $ex)
    {
        $string = '';

        while ($ex !== null) {
            $number = 0;
            $string .= "Exception ".get_class($ex).": '".$ex->getMessage()."'\n\n";

            foreach ($ex->getTrace() as $tracePart) {
                $line = false;
                if (isset($tracePart['file']) === true && isset($tracePart['line']) === true) {
                    $line .= $tracePart['file']." ";
                    $line .= $tracePart['line']." ";
                }
                else if (isset($tracePart['file']) === true) {
                    $line .= $tracePart['file']." ";
                }
                else if (isset($tracePart['line']) === true) {
                    $line .= $tracePart['line']." ";
                }
                else {
                    $line .= "*** "; // Some form of internal function or CUF
                }

                if (isset($tracePart["class"]) === true) {
                    $line .= $tracePart["class"];
                }
                if (isset($tracePart["type"]) === true) {
                    $line .= $tracePart["type"];
                }
                if (isset($tracePart["function"]) === true) {
                    $line .= $tracePart["function"];
                }

                $string .= sprintf("#%s %s\n", $number, $line);
                $number++;
            }
            $ex = $ex->getPrevious();
            if ($ex !== null) {
                $string .= "\nPrevious ";
            }
        };

        return $string;
    }


    public static function setupErrorHandlers()
    {
        register_shutdown_function(['Tier\Tier', 'tierShutdownFunction']);
        set_exception_handler(['Tier\Tier', 'tierExceptionHandler']);
        set_error_handler(['Tier\Tier', 'tierErrorHandler']);
    }


    /**
     * @param JigException $je
     * @param Request $request
     */
    public static function processJigException(
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
    public static function processInjectionException(
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
    public static function processInjectorException(
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
    public static function processException(
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
    public static function sendBodyResponse(
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
