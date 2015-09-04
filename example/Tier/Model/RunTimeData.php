<?php


namespace Tier\Model;


class RunTimeData {

    public $someVarThatIsDeterminedAtRuntime;

    function __construct($someVarThatIsDeterminedAtRuntime)
    {
        $this->someVarThatIsDeterminedAtRuntime = $someVarThatIsDeterminedAtRuntime;
    }
}

