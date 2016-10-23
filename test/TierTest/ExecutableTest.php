<?php

namespace TierTest;

use Tier\Executable;
use AurynConfig\InjectionParams;

class ExecutableTest extends BaseTestCase
{
    public function testPath()
    {
        $fn1 = function() {
            return "actual";
        };
            
        $fn2 = function() {
            return "setup";
        };
        
        $injectionParams = new InjectionParams();
        
        $exec = new Executable(
            $fn1,
            $injectionParams,
            $fn2
        );

        $exec->getInjectionParams();
        $callable = $exec->getCallable();
        $setupCallable = $exec->getSetupCallable();

        $this->assertEquals("setup", $setupCallable());
        $this->assertEquals("actual", $callable());
    }
}
