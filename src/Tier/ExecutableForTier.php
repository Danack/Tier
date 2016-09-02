<?php


namespace Tier;

class ExecutableForTier extends Executable
{
    /**
     * Which tier the executable should be run in.
     * @var null
     */
    private $tierNumber = null;

    public function __construct(
        $callable,
        $tierNumber,
        InjectionParams $injectionParams = null,
        $setupCallable = null,
        $skipIfProduced = null
    ) {
        $this->tierNumber = $tierNumber;
        parent::__construct(
            $callable,
            $injectionParams,
            $setupCallable,
            $skipIfProduced
        );
    }

    /**
     * @return null
     */
    public function getTierNumber()
    {
        return $this->tierNumber;
    }   
}
