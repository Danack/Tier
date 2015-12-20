<?php

namespace TierTest;

use Tier\ExceptionResolver;
use Fixtures\FooException;
use Fixtures\BarException;

class ExceptionResolverTest extends BaseTestCase
{
    public function testExceptionResolver()
    {
        $exceptionResolver = new ExceptionResolver();
        $fn = function() {
            return "Foo handler";
        };
        
        $defaultHandler = function() {
            return "Default handler";
        };

        $exceptionResolver->addExceptionHandler('FooException', $fn, 10);
        
        $exceptionResolver->addExceptionHandler(
            'Fixtures\FooException',
            $fn,
            ExceptionResolver::ORDER_LAST
        );

        $fooException = new FooException();
        $handlerInfo = $exceptionResolver->getExceptionHandler(
            $fooException,
            $defaultHandler
        );
        
        list($handler, $classname) = $handlerInfo;
        $this->assertEquals("Foo handler", $handler());
        
        $barException = new BarException();
        $handlerInfo = $exceptionResolver->getExceptionHandler(
            $barException,
            $defaultHandler
        );
        list($handler, $classname) = $handlerInfo;
        
        $this->assertEquals("Default handler", $handler());
    }
    
    public function testExceptionResolverError()
    {
        $exceptionResolver = new ExceptionResolver();
        $fn = function() {};
        $this->setExpectedException('Tier\TierException');
        $exceptionResolver->addExceptionHandler('FooException', $fn, -10);
    }
}
