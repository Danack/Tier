<?php

namespace TierTest;

use Tier\TierApp;
use Tier\InjectionParams;
use Tier\Executable;
use Tier\TierException;
use Tier\InvalidReturnException;
use Auryn\Injector;

class TierAppTest extends BaseTestCase
{
    public function testContinueContinues()
    {
        $injectionParams = new InjectionParams();
        $tierApp = new TierApp($injectionParams);
        
        $fn1 = function () {
            return false;
        };
        
        $called = false;
        
        $fn2 = function () use (&$called) {
            $called = true;
            return TierApp::PROCESS_END;
        };
        
        $tierApp->addExecutable(0, $fn1);
        $tierApp->addExecutable(0, $fn2);
        $tierApp->executeInternal();
        
        $this->assertTrue($called);
    }

    /**
     * @throws TierException
     */
    public function testInjectorParamsUsed()
    {
        $injectionParams = new InjectionParams();
        $tierApp = new TierApp($injectionParams);

        //Create the second function first, so it can be use'd
        $fooDebug = null;
        $fn2 = function($foo) use (&$fooDebug) {
            $fooDebug = $foo;
            return TierApp::PROCESS_END;
        };
        
        //First executable sets up the 'foo' param and runs the 2nd fn.
        $fn1 = function() use ($fn2) {
            $params = InjectionParams::fromParams(['foo' => 'bar']);
            return new Executable($fn2, $params);
        };

        $tierApp->addExecutable(0, $fn1);
        $tierApp->addExecutable(5, $fn2);
        $tierApp->executeInternal();
        $this->assertEquals('bar', $fooDebug);
    }

    public function testWrongReturnType_DefaultReturnExecutable()
    {
        $injectionParams = new InjectionParams();
        $tierApp = new TierApp($injectionParams);
        $fn1 = function () {
            // As this is processed as an executable.
            // The default return of null should be detected as an error.
        };
        
        $executable = new Executable($fn1);
        
        $tierApp->addExecutable(0, $executable);
        try {
            $tierApp->executeInternal();
        }
        catch (InvalidReturnException $ie) {
            $this->assertNull($ie->getValue());
            return;
        }

        $this->fail("InvalidReturnException was not thrown.");
    }
    
    
    public function testCallableAllowedToReturnNull()
    {
        $injectionParams = new InjectionParams();
        $tierApp = new TierApp($injectionParams);
        $fn1 = function () {
            // Because this is added as a callable, not an exception
            // The default return of null is allowed.
        };
        
        $tierApp->addExecutable(0, $fn1);
        $tierApp->executeInternal();
    }

    

    public function testWrongReturnType_ObjectReturn()
    {
        $injectionParams = new InjectionParams();
        $tierApp = new TierApp($injectionParams);
        $object = new \StdClass;
        $fn1 = function () use ($object) {
            return $object;
        };
        
        $tierApp->addExecutable(0, $fn1);
        try {
            $tierApp->executeInternal();
        }
        catch (InvalidReturnException $ie) {
            $this->assertEquals($object, $ie->getValue());
            return;
        }

        $this->fail("InvalidReturnException was not thrown.");
    }

    public function testWrongReturnType_ScalarReturn()
    {
        $injectionParams = new InjectionParams();
        $tierApp = new TierApp($injectionParams);
        $fn1 = function () {
            return "This is not an executable";
        };
        
        $tierApp->addExecutable(0, $fn1);
        $this->setExpectedException('Tier\InvalidReturnException');
        $tierApp->executeInternal();
    }
    

    public function testMultipleExecutablesFinishCurrentStage()
    {
        $injectionParams = new InjectionParams();
        $tierApp = new TierApp($injectionParams);
        
        $tierApp->addExpectedProduct('StdClass');
        
        $fn1 = function () {
            $obj = new \StdClass;
            $obj->foo = 'bar';
            return $obj;
        };
        $fn2 = function () {
            throw new \Exception("This should not be reached, ");
        };
        
        $object = null;
        
        $fn3 = function (\StdClass $stdClass) use (&$object) {
            $object = $stdClass;
            return TierApp::PROCESS_END;
        };
        
        // Add two executables to the initial stage. The first one returning
        // an expected product should stop the other from being executing;
        $tierApp->addExecutable(0, $fn1);
        $tierApp->addExecutable(0, $fn2);
        $tierApp->addExecutable(5, $fn3);
        $tierApp->executeInternal();

        $this->assertObjectHasAttribute('foo', $object);
        $this->assertEquals($object->foo, 'bar');
    }


    /**
     * This is the same as the testMultipleExecutablesFinishCurrentStage except
     * that the result produced is a sub-class of the expected result.
     * @throws TierException
     */
    public function testMultipleExecutablesFinishCurrentStageWithAliasedResult()
    {
        $injectionParams = new InjectionParams();
        $tierApp = new TierApp($injectionParams);
        $tierApp->addExpectedProduct('StdClass');
        $fn1 = function () {
            $obj = new \Fixtures\FooResult;
            $obj->foo = 'bar';
            return $obj;
        };
        $fn2 = function () {
            throw new \Exception("This should not be reached, ");
        };

        $object = null;
        $fn3 = function (\StdClass $stdClass) use (&$object) {
            $object = $stdClass;
            return TierApp::PROCESS_END;
        };
        
        // Add two executables to the initial stage. The first one returning
        // an expected product should stop the other from being executing;
        $tierApp->addExecutable(0, $fn1);
        $tierApp->addExecutable(0, $fn2);
        $tierApp->addExecutable(5, $fn3);
        $tierApp->executeInternal();

        $this->assertObjectHasAttribute('foo', $object);
        $this->assertEquals($object->foo, 'bar');
    }

    /**
     * @throws TierException
     */
    public function testRecursionError()
    {
        $injectionParams = new InjectionParams();
        $tierApp = new TierApp($injectionParams);
        $this->setExpectedException('Tier\TierException', 'Too many tiers');
        $fn = null;
        $fn = function() use (&$fn) {
            //This will get run as the next tier.
            return new Executable($fn);
        };

        $tierApp->addExecutable(0, $fn);
        $tierApp->executeInternal();
    }

    public function testSilentOnMissingProcessEnd()
    {
        $injectionParams = new InjectionParams();
        $tierApp = new TierApp($injectionParams);

        $fn = function() {
            return TierApp::PROCESS_CONTINUE;
        };

        $tierApp->addExecutable(0, $fn);
        $tierApp->executeInternal();
    }

    public function testExceptionOnMissingProcessEnd()
    {
        $injectionParams = new InjectionParams();
        $tierApp = new TierApp($injectionParams);
        $tierApp->warnOnSilentProcessingEnd = true;
        
        $fn = function() {
            return TierApp::PROCESS_CONTINUE;
        };

        $tierApp->addExecutable(0, $fn);
        $this->setExpectedException('Tier\TierException', 'TierApp::PROCESS_END');
        $tierApp->executeInternal();
    }

    public function testCoverage1()
    {
        $injectionParams = new InjectionParams();
        $injector = new Injector();
        $tierApp = new TierApp($injectionParams, $injector);

        $execCalled = false;
        $fn2 = function () use (&$execCalled) {
            $execCalled = true;
            return TierApp::PROCESS_END;
        };

        $setupCalled = false;
        $setupCallable = function() use (&$setupCalled) {
            $setupCalled = true;
        };
                
        $fn1 = function () use ($fn2, $setupCallable) {
            return new Executable($fn2, null, $setupCallable);
        };

        $tierApp->addExecutable(0, $fn1);
        $tierApp->executeInternal();
        $this->assertTrue($execCalled);
        $this->assertTrue($setupCalled);
    }

   
    public function testReturnArray()
    {
        $injectionParams = new InjectionParams();

        $tierApp = new TierApp($injectionParams);

        $fn2aCalled = false;
        $fn2bCalled = false;
        $fn2cCalled = false;

        $fn2a = function () use (&$fn2aCalled) {
            $fn2aCalled = true;
            return false;
        };

        $fn2b = function () use (&$fn2bCalled) {
            $fn2bCalled = true;
            return TierApp::PROCESS_END_STAGE;
        };
        
        $fn2c = function () use (&$fn2cCalled) {
            $fn2cCalled = true;
        };
                
        $fn1 = function () use ($fn2a, $fn2b, $fn2c) {
            $executables = [];
            $executables[] = new Executable($fn2a);
            $executables[] = new Executable($fn2b);
            $executables[] = new Executable($fn2c);

            return $executables;
        };
        
        $fnEnd = function() {
            return TierApp::PROCESS_END;
        };

        $tierApp->addExecutable(0, $fn1);
        $tierApp->addExecutable(10, $fnEnd);
        
        $tierApp->executeInternal();
        $this->assertTrue($fn2aCalled, '$fn2aCalled not called');
        $this->assertTrue($fn2bCalled, '$fn2bCalled not called');
        $this->assertFalse($fn2cCalled, '$fn2cCalled not called');
    }

    public function testReturnArrayError()
    {
        $injectionParams = new InjectionParams();
        $tierApp = new TierApp($injectionParams);

        $fn1 = function() {
            $executables = [];
            $executables[] = "This is not an execuable";

            return $executables;
        };
        
        $fnEnd = function() {
            return TierApp::PROCESS_END;
        };

        $tierApp->addExecutable(0, $fn1);
        $tierApp->addExecutable(10, $fnEnd);
        
        $this->setExpectedException('Tier\InvalidReturnException');
        $tierApp->executeInternal();
    }
    
    public function testProductionSkipsExecutable()
    {
        $injectionParams = new InjectionParams();
        $tierApp = new TierApp($injectionParams);

        $fn1 = function() {
            return new \StdClass();
        };
        
        $fn2 = function() {
            throw new \Exception("This shouldn't be reached.");
        };
        
        $tierApp->addExpectedProduct('StdClass');
        $executable = new Executable($fn2, null, null, 'StdClass');

        //The first execution creates and shares a StdClass object.
        $tierApp->addExecutable(0, $fn1);
        
        //The second executable should be skipped.
        $tierApp->addExecutable(2, $executable);
        
        $fn3 = function () {
            return \Tier\TierApp::PROCESS_END;
        };
        
        $tierApp->addExecutable(5, $fn3);

        $tierApp->executeInternal();
    }

    /**
     * This just covers a line in TierApp::hasExpectedProductBeenProduced
     * @throws TierException
     */
    public function testUnknownExpectedProduct()
    {
        $injectionParams = new InjectionParams();
        $tierApp = new TierApp($injectionParams);

        $fn1 = function() {
            return \Tier\TierApp::PROCESS_CONTINUE;
        };
        
        $fn2 = function() {
            return \Tier\TierApp::PROCESS_END;
        };

        $tierApp->addExpectedProduct('StdClass');
        $executable = new Executable($fn2, null, null, 'UnknownClass');
        
        $tierApp->addExecutable(0, $fn1);
        $tierApp->addExecutable(2, $executable);
        $tierApp->executeInternal();
    }

    public function testDuplicateExpectedProduct()
    {
        $injectionParams = new InjectionParams();
        $tierApp = new TierApp($injectionParams);
        
        $tierApp->addExpectedProduct('StdClass');
        
        $this->setExpectedException('Tier\TierException');
        $tierApp->addExpectedProduct('StdClass');
    }
    
    
    public function testReturnInjectionParams()
    {
        $injectionParams = new InjectionParams();
        $tierApp = new TierApp($injectionParams);
        
        
        $addInjectionParamsFn = function() {
            $injectionParams = new InjectionParams();
            $injectionParams->alias('Fixtures\FooInterface', 'Fixtures\FooImplementation');

            return $injectionParams;
        };
        
        // When tier tries to instantiate this, it will fail if the alias
        // hasn't been added.
        $requiresInterfaceFn = function (\Fixtures\FooInterface $foo) {
            return TierApp::PROCESS_END;
        };

        $tierApp->addExecutable(10, $addInjectionParamsFn);
        $tierApp->addExecutable(20, $requiresInterfaceFn);
        $tierApp->executeInternal();
    }
}
