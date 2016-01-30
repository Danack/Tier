<?php

namespace Tier;

use Tier\Executable;

class InvalidReturnException extends \Exception
{
    private $value;

    const RETURN_VALUE = "An Executable must return one of Executable, a TierApp::PROCESS_* constant, an 'expectedProduct' or an array of Executables.";
    
    /**
     * @param string $message
     * @param mixed $value The value that was not an acceptable return for a tier.
     * @param int $code
     * @param \Exception $previous
     */
    public function __construct($message, $value, $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
    
    /**
     * @param $result
     * @return TierException
     */
    public static function getWrongTypeException($result, Executable $executable)
    {
        $messageStart = self::RETURN_VALUE;
        $messageStart .= " Instead %s returned, when calling executable %s";
        
        if ($result === null) {
            $detail = 'null';
        }
        else if (is_object($result) === true) {
            $detail = "object of type '".get_class($result)."'";
        }
        else {
            $detail = "variable of type '".gettype($result)."'";
        }
    
        $callableInfo = var_export($executable->getCallable(), true);
        
        $message = sprintf(
            $messageStart,
            $detail,
            $callableInfo
        );
    
        return new InvalidReturnException($message, $result);
    }
}
