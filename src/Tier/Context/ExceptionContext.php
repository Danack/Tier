<?php

namespace Tier\Context;

/**
 * Class ExceptionContext
 * @package Tier\Context
 */
class ExceptionContext
{
    /** @var \Throwable|\Exception */
    private $exception;
    
    private function __construct($exception)
    {
        $this->exception = $exception;
    }
    
    public static function fromException(\Exception $e)
    {
        return new self($e);
    }

    public static function fromThrowable(\Throwable $e)
    {
        return new self($e);
    }

    /**
     * @return \Throwable|\Exception
     */
    public function getException()
    {
        return $this->exception;
    }
}
