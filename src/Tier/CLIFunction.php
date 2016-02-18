<?php

namespace Tier;

/**
 * Class CLIFunction
 * Set of utility functions for CLI applications.
 */
class CLIFunction
{
    public static function setupErrorHandlers()
    {
        $initialOBLevel = ob_get_level();
        $shutdownFunction = function () use ($initialOBLevel) {
            while (ob_get_level() > $initialOBLevel) {
                ob_end_clean();
            }
            self::fatalErrorShutdownHandler();
        };

        register_shutdown_function($shutdownFunction);
        set_exception_handler(['Tier\CLIFunction', 'exceptionHandler']);
        set_error_handler(['Tier\CLIFunction', 'errorHandler']);
    }
    
    public static function exceptionHandler(\Exception $ex)
    {
        //TODO - need to ob_end_clean as many times as required because
        //otherwise partial content gets sent to the client.
        while ($ex !== null) {
            echo "Exception ".get_class($ex).': '.$ex->getMessage()."<br/>";
    
            foreach ($ex->getTrace() as $tracePart) {
                if (isset($tracePart['file']) === true && isset($tracePart['line']) === true) {
                    echo $tracePart['file']." ".$tracePart['line']."<br/>";
                }
                else if (isset($tracePart["function"]) === true) {
                    echo $tracePart["function"] . "<br/>";
                }
                else {
                    var_dump($tracePart);
                }
            }
            $ex = $ex->getPrevious();
            if ($ex !== null) {
                echo "Previously ";
            }
        };
        
        exit(-1);
    }

    public static function fatalErrorShutdownHandler()
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
    
        if ($lastError !== null && in_array($lastError['type'], $fatals) === true) {
            extract($lastError);
            $errorMessage = sprintf("Fatal error: %s in %s on line %d", $message, $file, $line);
    
            error_log($errorMessage);
            $msg = "Oops! Something went terribly wrong :(";
            $msg .= $errorMessage;
            $msg .= "\n";
    
            echo $msg;
            
            exit(-1);
        }
    }

    public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        if (error_reporting() === 0) {
            return true;
        }
        if ($errno === E_DEPRECATED) {
            // In general we don't care.
            return true;
        }
    
        $errorNames = [
            E_ERROR => "E_ERROR",
            E_WARNING => "E_WARNING",
            E_PARSE => "E_PARSE",
            E_NOTICE => "E_NOTICE",
            E_CORE_ERROR => "E_CORE_ERROR",
            E_CORE_WARNING => "E_CORE_WARNING",
            E_COMPILE_ERROR => "E_COMPILE_ERROR",
            E_COMPILE_WARNING => "E_COMPILE_WARNING",
            E_USER_ERROR => "E_USER_ERROR",
            E_USER_WARNING => "E_USER_WARNING",
            E_USER_NOTICE => "E_USER_NOTICE",
            E_STRICT => "E_STRICT",
            E_RECOVERABLE_ERROR => "E_RECOVERABLE_ERROR",
            E_DEPRECATED => "E_DEPRECATED",
            E_USER_DEPRECATED => "E_USER_DEPRECATED",
        ];
        
        $errorType = "Error type $errno";
    
        if (array_key_exists($errno, $errorNames) === true) {
            $errorType = $errorNames[$errno];
        }
    
        $message =  "$errorType: [$errno] $errstr in file $errfile on line $errline";
    
        throw new \ErrorException($message);
    }
    
    public static function handleInvalidReturnException(\Tier\InvalidReturnException $ire)
    {
        $message = sprintf(
            "Tier\\InvalidReturnException: %s ".PHP_EOL,
            $ire->getMessage()
        );
        echo $message;
        exit(-2);
    }
    
    public static function handleThrowable(\Throwable $t)
    {
        $message = sprintf(
            "Unexpected exception of type %s running Deployer`: %s".PHP_EOL,
            get_class($t),
            $e->getMessage()
        );
        echo $message;
        echo \Tier\TierFunction::getExceptionString($e);
        exit(-2);
    }
    
    public static function handleException(\Exception $e)
    {
        $message = sprintf(
            "Unexpected exception of type %s running Deployer`: %s".PHP_EOL,
            get_class($e),
            $e->getMessage()
        );
        echo $message;
        echo \Tier\TierFunction::getExceptionString($e);
        exit(-2);
    }
}
