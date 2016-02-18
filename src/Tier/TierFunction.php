<?php

namespace Tier;

use Room11\HTTP\Body;

class TierFunction
{

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
}
