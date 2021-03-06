<?php

namespace Tier;

use Auryn\Injector;
use AurynConfig\InjectionParams;

/**
 * Class TierApp
 * @package Tier
 */
class TierApp
{
    /**
     * @var \Tier\ExecutableListByTier
     */
    protected $executableListByTier;

    /** @var Injector The DIC that runs the app */
    protected $injector;

    // A set of constants that flag how the flow control of the application should
    // proceed.
    //
    // Running of the application should continue.
    const PROCESS_CONTINUE = 1;

    // Application has finished running
    const PROCESS_END = 2;
    
    // Application should finish looping, and move onto the shut-down routines
    //const PROCESS_END_LOOPING = 4;

    /**
     * The expected products are the names of objects that the application is expected to
     * produce. They will automatically be shared to be made available for processing by
     * later Tiers. For example in a web-server application, a response body would be an
     * appropriate expected result.
     * @var array[string]
     */
    protected $expectedProducts = [];

    /**
     * Whether to throw an exception if processing ends without the PROCESS_END constant
     * being returned. This would only be appropriate to use for a small number of applications.
     * @var bool
     */
    public $warnOnSilentProcessingEnd = false;

    //const RETURN_VALUE = "An Executable must return one of Executable, a TierApp::PROCESS_* constant, an 'expectedProduct' or an array of Executables.";
    const RETURN_VALUE = "An Executable must return one of Executable, a TierApp::PROCESS_* constant, or an 'expectedProduct'";

    /** @var \callable|null */
    protected $loopCallback = null;

    /**
     * @param Injector $injector
     * @param callable $loopCallback
     */
    public function __construct(
        Injector $injector,
        callable $loopCallback
    ) {
        $this->injector = $injector;
        $this->executableListByTier = new ExecutableListByTier();
        $this->loopCallback = $loopCallback;
    }

    /**
     * @throws TierException
     */
    public function executeInternal()
    {
        // Create and share these as they need to be the same
        // across the application
        $this->injector->share($this->injector); //yolo

        foreach ($this->executableListByTier as $appStage => $executable) {
            if ($this->loopCallback !== null) {
                $callback = $this->loopCallback;
                $callback();
            }

            //Some executables shouldn't be run if a certain product
            //has already been made. This allows very easy caching layers.
            $skipIfProduced = $executable->getSkipIfExpectedProductProduced();
            if ($skipIfProduced !== null &&
                $this->hasExpectedProductBeenProduced($skipIfProduced) === true) {
                continue;
            }

            $result = self::executeExecutable($executable, $this->injector);
            $finished = $this->processResult($result, $executable);

            if ($finished === self::PROCESS_END) {
                //echo "This is breaking because of PROCESS_END";
                return;
            }
        }

        if ($this->warnOnSilentProcessingEnd === true) {
            throw new TierException("Processing did not result in a TierApp::PROCESS_END");
        }
    }

    /**
     * @param Executable $tier
     * @param Injector $injector
     * @return mixed
     */
    public static function executeExecutable(Executable $tier, Injector $injector)
    {
        // Setup the information created by the previous Tier.
        if (($injectionParams = $tier->getInjectionParams()) !== null) {
            $injectionParams->addToInjector($injector);
        }

        // If the next Tier has a setup function, call it.
        $setupCallable = $tier->getSetupCallable();
        if ($setupCallable !== null) {
            $injector->execute($setupCallable);
        }
        // Call this Tier's callable.
        return $injector->execute($tier->getCallable());
    }


    /**
     * @param $result mixed The result produced by running the previous executable.
     * @param Executable $executable The executable that was just run to produce
     * the result.
     * @return int
     * @throws TierException
     * @throws \Auryn\ConfigException
     */
    protected function processResult($result, Executable $executable)
    {
        // If it's a new Tier to run, setup the next loop.
        if ($result instanceof InjectionParams) {
            $result->addToInjector($this->injector);
            return self::PROCESS_CONTINUE;
        }

        // If it's a new Tier to run, setup the next loop.
        if ($result instanceof Executable) {
            $this->executableListByTier->addExecutable($result);
            return self::PROCESS_CONTINUE;
        }
        
        if ($result === null && $executable->isAllowedToReturnNull() === true) {
            return self::PROCESS_CONTINUE;
        }

//        if (is_array($result) === true &&
//            count($result) !== 0) {
//            //It's an array of tiers to run.
//            foreach ($result as $executableOrCallable) {
//                if (($executableOrCallable instanceof Executable) === true) {
//                    $this->executableListByTier->addExecutable($executableOrCallable);
//                    continue;
//                }
//                else if (is_callable($executableOrCallable) === true) {
//                    $newExecutable = new Executable($executableOrCallable);
//                    $this->executableListByTier->addExecutable($newExecutable);
//                    continue;
//                }
//
//                throw InvalidReturnException::getWrongTypeException($result, $executable);
//            }
//            return self::PROCESS_CONTINUE;
//        }

        if ($result === false) {
            // The executed tier wasn't able to handle it e.g. a caching layer
            // There should be another tier to execute in this stage.
            return self::PROCESS_CONTINUE;
        }

        // If the $result is an expected product share it for further stages
        foreach ($this->expectedProducts as $expectedProduct => $created) {
            if ($result instanceof $expectedProduct) {
                if (is_subclass_of($result, $expectedProduct) === true) {
                    //product is a sub-class of the expected product. Setup an
                    //alias for it
                    $this->injector->alias($expectedProduct, get_class($result));
                }
                $this->expectedProducts[$expectedProduct] = true;
                $this->injector->share($result);
                return self::PROCESS_CONTINUE;
            }
        }

        if (//$result === self::PROCESS_END_STAGE ||
            $result === self::PROCESS_CONTINUE ||
            $result === self::PROCESS_END
            // || $result === self::PROCESS_END_LOOPING
        ) {
            return $result;
        }

        // Otherwise it's an error
        throw InvalidReturnException::getWrongTypeException($result, $executable);
    }

    /**
     * Adds an expected product. Expected products will be automatically
     * shared to further tiers.
     *
     * For example a HTTP app could set a response body as the
     * expected product.
     * @param $classname
     * @throws TierException
     */
    public function addExpectedProduct($classname)
    {
        $classname = strtolower($classname);
        if (array_key_exists($classname, $this->expectedProducts) === true) {
            throw new TierException("Expected product $classname is already added.");
        }
        $this->expectedProducts[$classname] = false;
    }

    /**
     * @param $classname
     * @return bool
     */
    public function hasExpectedProductBeenProduced($classname)
    {
        $classname = strtolower($classname);
        if (array_key_exists($classname, $this->expectedProducts) === false) {
            return false;
        }
        return $this->expectedProducts[$classname];
    }
    
    /**
     * @param $tier
     * @param $executable
     */
    public function addExecutable($tier, $executable)
    {
        $this->executableListByTier->addExecutableToTier($tier, $executable);
    }
}
