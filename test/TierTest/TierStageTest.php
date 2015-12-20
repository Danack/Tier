<?php


namespace TierTest;

use Tier\ExecutableListByTier;
use Auryn\Injector;

class TierStageTest extends BaseTestCase
{

    public function testStageRunning()
    {
        $functionsCalled = [];
        
        $tiersByStage = new ExecutableListByTier();
        
        $fn2 = function () use (&$functionsCalled) {
            $functionsCalled[2] = true;
        };

        $fn0 = function () use (&$functionsCalled, $tiersByStage, $fn2) {
            $functionsCalled[0] = true;
            $tiersByStage->addExecutable(4, $fn2);
        };
        $fn1 = function () use (&$functionsCalled) {
            $functionsCalled[1] = true;
        };
        
        $tiersByStage->addExecutable(2, $fn0);
        $tiersByStage->addExecutable(2, $fn1);
                
        $injector = new Injector();
        
        foreach ($tiersByStage as $appStage => $tiersForStage) {
            foreach ($tiersForStage as $tier) {
                $injector->execute($tier);
            }
        }

        $this->assertArrayHasKey(0, $functionsCalled);
        $this->assertArrayHasKey(1, $functionsCalled);
        $this->assertArrayHasKey(2, $functionsCalled);
    }
}
