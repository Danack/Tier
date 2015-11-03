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
     * @var array[int][Tier]
     */
    protected $tiersByStage;

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

    /** @var array  */
    protected $expectedProducts = [];
    

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
        
        $this->tiersByStage = new ExecutableListByTier();
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
        foreach ($this->tiersByStage as $appStage => $tiersForStage) {
            foreach ($tiersForStage as $tier) {
                //Check we haven't got caught in a redirect loop
                $this->internalExecutions++;
                if ($this->internalExecutions > $this->maxInternalExecutions) {
                    throw new TierException("Too many tiers");
                }

                /** @var $tier Executable  */
                if ($tier instanceof Executable) {
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
            $this->tiersByStage->addNextStageTier($result);
            return self::PROCESS_CONTINUE;
        }
        if (is_array($result)) {
            //It's an array of tiers to run.
            foreach ($result as $tier) {
                if (!$tier instanceof Executable) {
                    throw new TierException(
                        "A tier must return either a responsebody, a new Tier or an array of Tiers"
                    );
                }
                $this->tiersByStage->addNextStageTier($tier);
            }
            return self::PROCESS_CONTINUE;
        }
        if ($result == false) {
            // The executed tier wasn't able to handle it e.g. a caching layer
            // There should be another tier to execute in this stage.
            return self::PROCESS_CONTINUE;
        }

        // If the $result is an expected product share it for further stages
        foreach ($this->expectedProducts as $product) {
            if ($result instanceof $product) {
                $this->injector->alias($product, get_class($result));
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
        throw throwWrongTypeException($result);
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
        $this->expectedProducts[] = $classname;
    }
}
