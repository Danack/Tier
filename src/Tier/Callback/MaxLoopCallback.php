<?php

namespace Tier\Callback;

use Tier\TierException;

class MaxLoopCallback
{
    /** @var int How many tiers/executables have been executed */
    protected $internalExecutions = 0;

    /** @var int Max limit for number of tiers/callables to execute.
     * Prevents problems with applications getting stuck in a loop.
     */
    public $maxInternalExecutions = 20;
    
    /**
     * For some applications (e.g. http server) we don't want to get caught in
     * an internal redirect loop.
     * @throws TierException
     */
    public function __invoke()
    {
        //Check we haven't got caught in a redirect loop
        $this->internalExecutions++;
        if ($this->internalExecutions > $this->maxInternalExecutions) {
            $message = "Too many tiers executed. You probably have a recursion error in your application.";
            throw new TierException($message, TierException::TOO_MANY_EXECUTES);
        }
    }
}
