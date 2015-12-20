<?php


namespace TierTest;

use Auryn\Injector;
use Tier\InjectionParams;
use Mockery;

class TierInjectionParamsTest extends BaseTestCase
{
    public function testFromParams()
    {
        $injector = new Injector();
        $injectionParams = InjectionParams::fromParams(['foo' => 'bar']);
        $injectionParams->addToInjector($injector);

        $fooTest = null;
        $fn = function($foo) use (&$fooTest) {
            $fooTest = $foo;
        };
        $injector->execute($fn);
        $this->assertEquals('bar', $fooTest);
 
        $this->assertInternalType('array', $injectionParams->getShares());
        $this->assertInternalType('array', $injectionParams->getAliases());
        $this->assertInternalType('array', $injectionParams->getParams());
        $this->assertInternalType('array', $injectionParams->getDelegates());
        $this->assertInternalType('array', $injectionParams->getPrepares());
        $this->assertInternalType('array', $injectionParams->getDefines());
        
    }
    
    public function testAlias()
    {
        $injectorMock = Mockery::mock('Auryn\Injector');
        $injectorMock
            ->shouldReceive('alias')
            ->with('foo', 'bar')
            ->once();

        $params = new InjectionParams();
        $params->alias('foo', 'bar');
        $params->addToInjector($injectorMock);
    }

    public function testShare()
    {
        $injectorMock = Mockery::mock('Auryn\Injector');
        $injectorMock
            ->shouldReceive('share')
            ->with('foo')
            ->once();

        $params = new InjectionParams();
        $params->share('foo');
        $params->addToInjector($injectorMock);
    }
    
    public function testDelegate()
    {
        $injectorMock = Mockery::mock('Auryn\Injector');
        $injectorMock
            ->shouldReceive('delegate')
            ->with('foo', 'callableFn')
            ->once();

        $params = new InjectionParams();
        $params->delegate('foo', 'callableFn');
        $params->addToInjector($injectorMock);
    }
    
    public function testPrepare()
    {
        $injectorMock = Mockery::mock('Auryn\Injector');
        $injectorMock
            ->shouldReceive('prepare')
            ->with('foo', 'callableFn')
            ->once();

        $params = new InjectionParams();
        $params->prepare('foo', 'callableFn');
        $params->addToInjector($injectorMock);
    }
    
    public function testDefine()
    {
        $injectorMock = Mockery::mock('Auryn\Injector');
        $injectorMock
            ->shouldReceive('define')
            ->with('foo', [':name' => 'value'])
            ->once();

        $params = new InjectionParams();
        $params->define('foo', [':name' => 'value']);
        $params->addToInjector($injectorMock);
    }

    public function testDefineParam()
    {
        $injectorMock = Mockery::mock('Auryn\Injector');
        $injectorMock
            ->shouldReceive('defineParam')
            ->with('foo', 'bar')
            ->once();

        $params = new InjectionParams();
        $params->defineParam('foo', 'bar');
        $params->addToInjector($injectorMock);
    }
}
