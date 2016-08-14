<?php

namespace TierTest;

use Tier\Executable;
use Tier\ExecutableByTier;
use Tier\TierException;

class ExecutableByTierTest extends BaseTestCase
{
    public function testExecutableOutOfRange()
    {
        $executableListByTier = new ExecutableByTier();

        $fn1 = function () {
            $this->fail("This should never be reached.");
        };

        $this->setExpectedException('Tier\TierException', TierException::INCORRECT_VALUE);
        $executableListByTier->addExecutableToTier(
            ExecutableByTier::TIER_NUMBER_LIMIT + 1,
            $fn1
        );
    }
    

    public function testExecutableAddedPreviousTier()
    {
        $executableListByTier = new ExecutableByTier();

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
            $callable = $executable->getCallable();
            call_user_func($callable);
        }
    }

    public function testExecutable()
    {
        $execListByTier = new ExecutableByTier();

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
}
