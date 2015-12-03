<?php

namespace Tier;

use Auryn\Injector;

/**
 * Class TierApp
 * @package Tier
 */
class TierApp
{
    /** @var int How many tiers/callables have been executed */
    protected $internalExecutions = 0;

    /** @var int Max limit for number of tiers/callables to execute.
     * Prevents problems with applications getting stuck in a loop.
     */
    public $maxInternalExecutions = 20;
    
    /**
     * @var \Tier\ExecutableListByTier
     */
    protected $executableListByTier;

    /**
     * @var \Tier\InjectionParams
     */
    protected $initialInjectionParams = null;

    /** @var Injector The DIC that runs the app */
    protected $injector;

    // A set of constants that flag how the flow control of the application should
    // proceed.
    //
    // Running of the application should continue.
    const PROCESS_CONTINUE = 1;
    // The current tier of the appliction has finished and should move to the next one.
    // This is useful for things like a caching service to indicate that the response
    // has been served from cache and the main processing body doesn't need to be run.
    const PROCESS_END_STAGE = 2;
    // Application has finished running
    const PROCESS_END = 3;

    /**
     * The expected products are the names of objects that the application is expected to
     * produce. They will automatically be shared to be made available for processing by
     * later Tiers. For example in a webserver application, a response body would be an
     * appropriate expected result.
     * @var array[string]
     */
    protected $expectedProducts = [];

    const RETURN_VALUE = "An Executable must return one of Executable, a TierApp::PROCESS_* constant, an 'expectedProduct' or an array of Executables.";

    /**
     * @param InjectionParams $injectionParams
     * @param Injector $injector
     */
    public function __construct(
        InjectionParams $injectionParams,
        Injector $injector = null
    ) {
        $this->initialInjectionParams = $injectionParams;

        if ($injector == null) {
            $this->injector = new Injector();
        }
        else {
            $this->injector = $injector;
        }
        
        $this->executableListByTier = new ExecutableListByTier();
    }

    /**
     * @throws TierException
     */
    public function executeInternal()
    {
        // Create and share these as they need to be the same
        // across the application
        $this->injector->share($this->injector); //yolo
        $this->initialInjectionParams->addToInjector($this->injector);
        foreach ($this->executableListByTier as $appStage => $tiersForStage) {
            foreach ($tiersForStage as $tier) {
                //Check we haven't got caught in a redirect loop
                $this->internalExecutions++;
                if ($this->internalExecutions > $this->maxInternalExecutions) {
                    $message = "Too many tiers executed. You probably have a recursion error in your application.";
                    throw new TierException($message);
                }

                /** @var $tier Executable  */
                if ($tier instanceof Executable) {
                    $skipIfProduced = $tier->getSkipIfProduced();
                    if ($skipIfProduced && 
                        $this->hasExpectedProductBeenProduced($skipIfProduced) == true) {
                        continue;
                    }

                    // Setup the information created by the previous Tier
                    if (($injectionParams = $tier->getInjectionParams())) {
                        $injectionParams->addToInjector($this->injector);
                    }

                    // If the next Tier has a setup function, call it
                    $setupCallable = $tier->getSetupCallable();
                    if ($setupCallable) {
                        $this->injector->execute($setupCallable);
                    }
                    // Call this Tier's callable
                    $result = $this->injector->execute($tier->getCallable());
                }
                else {
                    $result = $this->injector->execute($tier);
                }

                // TODO - if we need to allow user handlers this is the place they would go
                $finished = $this->processResult($result);
                
                if ($finished == self::PROCESS_END_STAGE) {
                    break;
                }
                if ($finished == self::PROCESS_END) {
                    return;
                }
            }
        }
        
        throw new TierException("Processing did not result in a TierApp::PROCESS_END");
    }

    /**
     * @param $result
     * @return int
     * @throws TierException
     */
    private function processResult($result)
    {
         // If it's a new Tier to run, setup the next loop.
        if ($result instanceof Executable) {
            $this->executableListByTier->addNextStageTier($result);
            return self::PROCESS_CONTINUE;
        }
        if (is_array($result) && count($result) != 0) {
            //It's an array of tiers to run.
            foreach ($result as $tier) {
                if (!$tier instanceof Executable) {
                    throw new InvalidReturnException(
                        self::RETURN_VALUE,
                        $result
                    );
                }
                $this->executableListByTier->addNextStageTier($tier);
            }
            return self::PROCESS_CONTINUE;
        }
        if ($result === false) {
            // The executed tier wasn't able to handle it e.g. a caching layer
            // There should be another tier to execute in this stage.
            return self::PROCESS_CONTINUE;
        }

        // If the $result is an expected product share it for further stages
        foreach ($this->expectedProducts as $expectedProduct => $created) {
            if ($result instanceof $expectedProduct) {
                if (strcasecmp($expectedProduct, get_class($result)) !== 0) {
                    //product is a sub-class of the expected product. Setup an
                    //alias for it
                    $this->injector->alias($expectedProduct, get_class($result));
                    $this->expectedProducts[$expectedProduct] = true;
                }
                $this->injector->share($result);
                return self::PROCESS_END_STAGE;
            }
        }

        if ($result === self::PROCESS_END_STAGE ||
            $result === self::PROCESS_CONTINUE ||
            $result === self::PROCESS_END) {
            return $result;
        }

        // Otherwise it's an error
        throw InvalidReturnException::getWrongTypeException($result);
    }

    /**
     * Adds an expected product. Expected products will be automatically
     * shared to further tiers.
     *
     * For example a HTTP app could set a response body as the
     * expected product.
     * @param $classname
     */
    public function addExpectedProduct($classname)
    {
        $classname = strtolower($classname);
        if (array_key_exists($classname, $this->expectedProducts)) {
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
        if (!array_key_exists($classname, $this->expectedProducts)) {
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
        $this->executableListByTier->addExecutable($tier, $executable);
    }
}
