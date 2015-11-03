<?php


namespace Tier;

class ExceptionResolver
{
    /**
     * The order that exception handlers are processed. You will want
     * to have more specific exception handlers attempt to handle an
     * exception before more generic handlers.
     */
    const ORDER_FIRST = 0;
    const ORDER_MIDDLE = 50;
    const ORDER_LAST = 100;

    /** @var array A set of  */
    private $exceptionHandlers = [];

    /**
     * @param $exceptionClassName
     * @param callable $callback
     * @param int $priority
     * @throws TierException
     */
    public function addExceptionHandler($exceptionClassName, callable $callback, $priority = 50)
    {
        $priority = intval($priority);
        if ($priority < 0 || $priority > 100) {
            $message = sprintf(
                "Priority of exception handler must be between 0 and 100; %s not acceptable.",
                $priority
            );
            
            throw new TierException($message);
        }

        $this->exceptionHandlers[$priority][$exceptionClassName] = $callback;
    }

    /**
     * @param \Exception $e
     * @param $defaultHandler
     * @return callable
     */
    public function getExceptionHandler(\Exception $e, $defaultHandler)
    {
        ksort($this->exceptionHandlers);
        foreach ($this->exceptionHandlers as $priority => $exceptionHandlerList) {
            foreach ($exceptionHandlerList as $classname => $handler) {
                if ($e instanceof $classname) {
                    return $handler;
                }
            }
        }
        
        return $defaultHandler;
    }
}
