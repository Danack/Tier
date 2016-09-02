<?php


namespace TierTest;

use Tier\ExecutableListByTier;
use Auryn\Injector;
use Tier\Executable;
use Tier\TierException;

class ExecutableListByTierTest extends BaseTestCase
{
    public function testExecutableOutOfRange()
    {
        $executableListByTier = new ExecutableListByTier();

        $fn1 = function () {
            $this->fail("This should never be reached.");
        };

        $this->setExpectedException('Tier\TierException', TierException::INCORRECT_VALUE);
        $executableListByTier->addExecutableToTier(
            ExecutableListByTier::TIER_NUMBER_LIMIT + 1,
            $fn1
        );
    }
    

    public function testExecutableAddedPreviousTier()
    {
        $executableListByTier = new ExecutableListByTier();

        $fn2 = function () {
            $this->fail("This should never be reached.");
        };
        
        $fn1 = function() use (&$executableListByTier, $fn2) {
            $executable = new Executable($fn2);
            $executable->setTierNumber(4);
            $executableListByTier->addExecutable($executable);
        };

        $executableListByTier->addExecutableToTier(5, $fn1);

        $this->setExpectedException('Tier\TierException', TierException::INCORRECT_VALUE);

        foreach ($executableListByTier as $tier => $executable) {
            $order[] = $tier;
            $callable = $executable->getCallable();
            call_user_func($callable);
        }
    }

    public function testExecutable()
    {
        $execListByTier = new ExecutableListByTier();

        $count = 0;
        $fn1Count = null;
        $fn2Count = null;
        $fn3Count = null;
        $fn2bCount = null;

        $fn2b = function() use (&$count, &$fn2bCount) {
            $fn2bCount = $count;
            $count++;
        };
        
        $fn1 = function() use (&$count, &$fn1Count) {
            $fn1Count = $count;
            $count++;
        };
        $fn2 = function() use (&$count, &$fn2Count, $execListByTier, $fn2b) {
            $fn2Count = $count;
            $count++;
            $execListByTier->addExecutable(new Executable($fn2b));
        };
        $fn3 = function() use (&$count, &$fn3Count) {
            $fn3Count = $count;
            $count++;
        };

        $execListByTier->addExecutableToTier(15, $fn3);
        $execListByTier->addExecutableToTier(5, $fn1);
        $execListByTier->addExecutableToTier(10, $fn2);

        $order = [];
        
        foreach ($execListByTier as $tier => $executable) {
            $order[] = $tier;
            $callable = $executable->getCallable();
            if (is_callable($callable) === false) {
                $this->fail("Callable returned by executable apparently isn't.");
            }
            
            call_user_func($callable);
        }
        
        //This checks that the fns were run in the correct order.
        $this->assertEquals(0, $fn1Count);
        $this->assertEquals(1, $fn2Count);
        $this->assertEquals(2, $fn2bCount);
        $this->assertEquals(3, $fn3Count);
        
        //Check that the things were ordered correctly.
        $this->assertEquals([5, 10, 11, 15], $order);
    }
    
    
    public function testStageRunning()
    {
        $functionsCalled = [];
        
        $executableList = new ExecutableListByTier();
        
        $fn2 = function () use (&$functionsCalled) {
            $functionsCalled[2] = true;
        };

        $fn0 = function () use (&$functionsCalled, $executableList, $fn2) {
            $functionsCalled[0] = true;
            $executableList->addExecutableToTier(2, $fn2);
        };
        $fn1 = function () use (&$functionsCalled) {
            $functionsCalled[1] = true;
        };
        
        $fn5 = function () use (&$functionsCalled) {
            $functionsCalled[5] = true;
        };
        
        $fn6 = function () use (&$functionsCalled) {
            $functionsCalled[6] = true;
        };
        
        $executableList->addExecutableToTier(0, $fn0);
        $executableList->addExecutableToTier(1, $fn1);
        $executableList->addExecutableToTier(6, $fn6);
        $executableList->addExecutableToTier(5, $fn5);
        

        foreach ($executableList as $appStage => $executable) {
            $callable = $executable->getCallable();
            $callable();
        }

        $this->assertArrayHasKey(0, $functionsCalled);
        $this->assertArrayHasKey(1, $functionsCalled);
        $this->assertArrayHasKey(2, $functionsCalled);
        $this->assertArrayHasKey(5, $functionsCalled);
        $this->assertArrayHasKey(6, $functionsCalled);
    }
}
