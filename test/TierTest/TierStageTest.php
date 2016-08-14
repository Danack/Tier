<?php


namespace TierTest;

use Tier\ExecutableByTier;
use Auryn\Injector;

class TierStageTest extends BaseTestCase
{

    public function testStageRunning()
    {
        $functionsCalled = [];
        
        $tiersByStage = new ExecutableByTier();
        
        $fn2 = function () use (&$functionsCalled) {
            $functionsCalled[2] = true;
        };

        $fn0 = function () use (&$functionsCalled, $tiersByStage, $fn2) {
            $functionsCalled[0] = true;
            $tiersByStage->addExecutableToTier(4, $fn2);
        };
        $fn1 = function () use (&$functionsCalled) {
            $functionsCalled[1] = true;
        };
        
        $tiersByStage->addExecutableToTier(2, $fn0);
        $tiersByStage->addExecutableToTier(3, $fn1);
        $injector = new Injector();

        foreach ($tiersByStage as $appStage => $executable) {
            $injector->execute($executable->getCallable());
        }

        $this->assertArrayHasKey(0, $functionsCalled);
        $this->assertArrayHasKey(1, $functionsCalled);
        $this->assertArrayHasKey(2, $functionsCalled);
    }
}
