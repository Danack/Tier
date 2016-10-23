<?php

namespace Tier;

use AurynConfig\InjectionParams;

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
     * @var \AurynConfig\InjectionParams
     */
    private $injectionParams;

    /**
     * If a class of this type has already been produced by an executable
     * (i.e. returned from an executable) then skip running this executable.
     * @var null|string
     */
    private $skipIfExpectedProductProduced;

    /**
     * For executables that are created directly from callable, having to return a
     * Tier::PROCESS_* constant is a burden.
     * @var bool
     */
    private $allowedToReturnNull = false;

    /**
     * Which tier the executable should be run in.
     * @var null
     */
    private $tierNumber = null;
    
    /**
     * @param $callable
     * @param \AurynConfig\InjectionParams $injectionParams
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
        $this->skipIfExpectedProductProduced = $skipIfProduced;
    }

    /**
     * @param $isAllowed
     */
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

    public function getSkipIfExpectedProductProduced()
    {
        return $this->skipIfExpectedProductProduced;
    }

    /**
     * @return null
     */
    public function getTierNumber()
    {
        return $this->tierNumber;
    }

    /**
     * @param null $tierNumber
     */
    public function setTierNumber($tierNumber)
    {
        $this->tierNumber = $tierNumber;
    }
}
