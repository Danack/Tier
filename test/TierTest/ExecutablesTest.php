<?php

namespace TierTest;

use Tier\Executable;
use Tier\ExecutableListByTier;
use Tier\TierException;

class ExecutablesTest extends BaseTestCase
{

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

        foreach ($executableListByTier as $tier => $executableList) {
            $order[] = $tier;
            foreach ($executableList as $position => $executable) {
                $callable = $executable->getCallable();
                call_user_func($callable);
            }
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
        $execPosition = [];
        
        foreach ($execListByTier as $tier => $executableList) {
            $order[] = $tier;
            foreach ($executableList as $position => $executable) {
                $callable = $executable->getCallable();
                if (is_callable($callable) === false) {
                    $this->fail("Callable returned by executable apparently isn't.");
                }
                
                call_user_func($callable);
                $execPosition[] = $position;
            }
        }
        
        //This checks that the fns were run in the correct order.
        $this->assertEquals(0, $fn1Count);
        $this->assertEquals(1, $fn2Count);
        $this->assertEquals(2, $fn2bCount);
        $this->assertEquals(3, $fn3Count);
        
        //Check that the things were ordered correctly.
        $this->assertEquals([5, 10, 11, 15], $order);
        //Check that the executables were the first item in each tier
        $this->assertEquals([0, 0, 0, 0], $execPosition);
    }
}
