<?php

namespace Tier;

class InvalidReturnException extends \Exception
{
    private $value;

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
    public static function getWrongTypeException($result)
    {
        $messageStart = TierApp::RETURN_VALUE;
        $messageStart .= " Instead %s returned.";
        
        if ($result === null) {
            $detail = 'null';
        }
        else if (is_object($result)) {
            $detail = "object of type '".get_class($result)."' .";
        }
        else {
            $detail = "variable of type '".gettype($result)."' returned.";
        }
    
        $message = sprintf(
            $messageStart,
            $detail
        );
    
        return new InvalidReturnException($message, $result);
    }
}
