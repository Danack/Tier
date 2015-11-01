<?php


namespace TierTest;

use Tier\ExecutablesByStage;
use Auryn\Injector;

class TierStageTest extends BaseTestCase
{

    function testStageRunning()
    {
        $functionsCalled = [];
        
        $tiersByStage = new ExecutablesByStage();
        
        $fn2 = function () use (&$functionsCalled) {
            $functionsCalled[2] = true;
        };

        $fn0 = function () use (&$functionsCalled, $tiersByStage, $fn2) {
            $functionsCalled[0] = true;
            $tiersByStage->addTier(4, $fn2);
        };
        $fn1 = function () use (&$functionsCalled) {
            $functionsCalled[1] = true;
        };
        
        $tiersByStage->addTier(2, $fn0);
        $tiersByStage->addTier(2, $fn1);
                
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
