<?php

namespace Tier;

/**
 * Class Executable
 *
 * Defines a Executable to be run in a tier of the application. The information it contains is used in the following
 * order:
 *
 * i) The injection params are added to the injector
 * ii) The setup callable is called.
 * iii) The tier callable is called.
 *
 * For all of the parameters that are 'callable' they cannot be typed hinted as
 * callable, as Tier also supports instance methods (e.g. ['foo', 'bar'] ) which
 * does not pass the callable test.
 */
class Executable
{
    /**
     * @var callable - callable as well as object instance methods e.g. ['classname', 'nonStaticMethodName']
     */
    private $callable;

    /**
     * @var callable - callable as well as object instance methods e.g. ['classname', 'nonStaticMethodName']
     */
    private $setupCallable;
    
    /**
     * @var InjectionParams
     */
    private $injectionParams;

    private $skipIfProduced;

    /**
     * For executables that are created directly from callable, having to return a
     * Tier::PROCESS_* constant is a burden. 
     * @var bool
     */
    private $allowedToReturnNull = false;

    /**
     * @param $callable
     * @param InjectionParams $injectionParams
     * @param null $setupCallable
     * @param null $skipIfProduced
     */
    public function __construct(
        $callable,
        InjectionParams $injectionParams = null,
        $setupCallable = null,
        $skipIfProduced = null
    ) {
        $this->callable = $callable;
        $this->injectionParams = $injectionParams;
        $this->setupCallable = $setupCallable;
        $this->skipIfProduced = $skipIfProduced;
    }

    public function setAllowedToReturnNull($isAllowed)
    {
        $this->allowedToReturnNull = $isAllowed;
    }
    
    /**
     * @return bool
     */
    public function isAllowedToReturnNull()
    {
        return $this->allowedToReturnNull;
    }
    
    /**
     * @return callable|mixed
     */
    public function getCallable()
    {
        return $this->callable;
    }

    /**
     * @return callable|null
     */
    public function getSetupCallable()
    {
        return $this->setupCallable;
    }

    /**
     * @return InjectionParams
     */
    public function getInjectionParams()
    {
        return $this->injectionParams;
    }

    public function getSkipIfProduced()
    {
        return $this->skipIfProduced;
    }
}
