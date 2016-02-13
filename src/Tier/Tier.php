<?php

namespace Tier;

use Auryn\InjectionException;
use Auryn\Injector;
use Auryn\InjectorException;
use Jig\Jig;
use Jig\JigBase;
use Jig\JigException;
use Room11\HTTP\Body;
use Room11\HTTP\Body\HtmlBody;
use Room11\HTTP\HeadersSet;
use Psr\Http\Message\ServerRequestInterface as Request;
use Tier\Body\ExceptionHtmlBody;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response\EmitterInterface;

class Tier
{
    public static $initialOBLevel = 0;

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
        self::clearOutputBuffer();
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

            return new Executable(['Tier\Tier', 'createHtmlBody'], $injectionParams);
        };

        return new Executable($fn);
    }

    /**
     * Helper function to allow template rendering to be easier.
     * @param $templateName
     * @param array $sharedObjects
     * @return Executable
     */
    public static function renderTemplateExecutable(
        $templateName,
        InjectionParams $injectionParams = null
    ) {
        if ($injectionParams === null) {
            $injectionParams = new InjectionParams();
        }
        
        $fn = function (Jig $jigRender) use ($templateName, $injectionParams) {
            $className = $jigRender->compile($templateName);
            $injectionParams->alias('Jig\JigBase', $className);
            $fn = function (Injector $injector) use ($className) {
                return $injector->make($className);
            };
            $injectionParams->delegate('Jig\JigBase', $fn);

            return new Executable(['Tier\Tier', 'createHtmlBody'], $injectionParams);
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

    public static function clearOutputBuffer()
    {
        //Need to ob_end_clean as many times as required because
        //otherwise partial content gets sent to the client.
        $obClearCount = 0;
        while (ob_get_level() > self::$initialOBLevel) {
            ob_end_clean();
            $obClearCount++;
        }
    }
    
    public static function tierExceptionHandler($ex)
    {
        self::clearOutputBuffer();
        $body = new ExceptionHtmlBody(self::getExceptionString($ex), 500);
        self::sendRawBodyResponse($body);
    }

    /**
     * @param $ex \Exception|\Throwable
     * @return string
     */
    public static function getExceptionString($ex)
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
     * @param JigException $je
     * @return int
     * @return int
     */
    public static function processJigException(JigException $je)
    {
        $exceptionString = Tier::getExceptionString($je);
        $body = new ExceptionHtmlBody($exceptionString, 500);
        self::sendRawBodyResponse($body);

        return \Tier\TierApp::PROCESS_END;
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
        $body .= Tier::getExceptionString($ie);
        $body = new ExceptionHtmlBody($body, 500);
        self::sendRawBodyResponse($body);

        return \Tier\TierApp::PROCESS_END;
    }

    /**
     * @param InjectorException $ie
     * @return int
     * @return int
     * @internal param Request $request
     */
    public static function processInjectorException(InjectorException $ie)
    {
        $exceptionString = Tier::getExceptionString($ie);
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
        $exceptionString = Tier::getExceptionString($e);
        $body = new ExceptionHtmlBody($exceptionString, 500);
        self::sendRawBodyResponse($body);

        return \Tier\TierApp::PROCESS_END;
    }

    /**
     * @param \Exception $e
     * @return int
     * @return int
     */
    public static function processExceptionCLI(\Exception $e)
    {
        $exceptionString = Tier::getExceptionString($e);
        echo $exceptionString;
        exit(-1);
        
//        $body = new ExceptionHtmlBody($exceptionString, 500);
//        self::sendRawBodyResponse($body);

        return \Tier\TierApp::PROCESS_END;
    }

    
    
    /**
     * @param \Exception $e
     * @return int
     * @return int
     */
    public static function processThrowable(\Throwable $e)
    {
        $exceptionString = Tier::getExceptionString($e);
        $body = new ExceptionHtmlBody($exceptionString, 500);
        self::sendRawBodyResponse($body);

        return \Tier\TierApp::PROCESS_END;
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
}
