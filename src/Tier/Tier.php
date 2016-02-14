<?php

namespace Tier;

use Auryn\Injector;
use Jig\Jig;
use Room11\HTTP\Body;

class Tier
{
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

    /**
     * @param \Exception $e
     * @return int
     * @return int
     */
    public static function processExceptionCLI(\Exception $e)
    {
        $exceptionString = Tier::getExceptionString($e);
        echo $exceptionString;
    }
}
