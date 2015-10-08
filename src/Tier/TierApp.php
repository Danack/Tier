<?php

namespace Tier;

use Auryn\Injector;
use Room11\HTTP\Request;
use Room11\HTTP\Response\Response;
use Room11\HTTP\Body;

use Auryn\InjectorException;
use Auryn\InjectionException;
use Jig\JigException;
use Tier\ResponseBody\ExceptionHtmlBody;

class TierApp
{
    /**
     * @var Tier[]
     */
    private $tiers = [];

    private $tierCount = 0;

    public $maxInternalExecutes = 10;

    private $preCallables = [];

    private $postCallables = [];
    
    private $initialInjectionParams = null;
    
    private $exceptionHandlers = [];
    
    const FIRST = 0;
    const MIDDLE = 50;
    const LAST = 100;

    public function __construct(
        Tier $tier,
        InjectionParams $injectionParams = null,
        Injector $injector = null
    ) {
        $this->tiers[] = $tier;
        $this->initialInjectionParams = $injectionParams;

        if ($injector == null) {
            $this->injector = new Injector();
        }
    }
    
    public function setStandardExceptionHandlers()
    {
        $this->addExceptionHandler(
            JigException::class,
            'Tier\processJigException',
            self::MIDDLE
        );
        
        $this->addExceptionHandler(
            InjectorException::class,
            'Tier\processInjectorException',
            self::LAST - 2
        );

        $this->addExceptionHandler(
            InjectionException::class,
            'Tier\processInjectionException',
            self::MIDDLE
        );

        $this->addExceptionHandler(
            \Exception::class,
            'Tier\processException',
            self::LAST
        );

    }

    public function addTier(Tier $tier)
    {
        $this->tiers[] = $tier;
    }

    // This can't be type-hinted as callable as we allow instance methods
    // on uncreated classes.
    public function addPreCallable($callable)
    {
        $this->preCallables[] = $callable;
    }

    // This can't be type-hinted as callable as we allow instance methods
    // on uncreated classes.
    public function addPostCallable($callable)
    {
        $this->postCallables[] = $callable;
    }
    
    public function addExceptionHandler($exceptionClassName, callable $callback, $priority = 50)
    {
        $priority = intval($priority);
        if ($priority < 0 || $priority > 100) {
            throw new TierException("Priority of exception handler must be between 0 and 100; $priority not acceptable.");
        }

        $this->exceptionHandlers[$priority][$exceptionClassName] = $callback;
    }
    

    public function execute(Request $request)
    {
        try {
            $this->executeInternal($request);
        }
        catch (\Exception $e) {
            $this->processException($e, $request);
        }
    }
    
    
    public function executeInternal(Request $request)
    {
        // Create and share these as they need to be the same
        // across the application
        $response = new Response;
        $this->injector->share($request);
        $this->injector->alias(Request::class, get_class($request));
        $this->injector->share($response);
        $this->injector->share($this->injector); //yolo

        $this->initialInjectionParams->addToInjector($this->injector);

        foreach ($this->preCallables as $preCallable) {
            $this->injector->execute($preCallable);
        }

        while (true) {
            if ($this->tierCount >= count($this->tiers)) {
                throw new TierException("No more Tiers to execute");
            }

            $tier = $this->tiers[$this->tierCount];

            //Check we haven't got caught in a redirect loop
            $this->tierCount++;
            if ($this->tierCount > $this->maxInternalExecutes) {
                throw new TierException("Too many tiers");
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
            $result = $this->injector->execute($tier->getTierCallable());

            // If it's a responseBody send it
            if ($result instanceof Body) {                
                $response->setBody($result);
                sendResponse($request, $response);
                break;
            } // If it's a new Tier to run, setup the next loop.
            else if ($result instanceof Tier) {
                $this->tiers[] = $result;
            }
            else if (is_array($result)) {
                //It's an array of tiers to run.
                foreach ($result as $tier) {
                    if (!$tier instanceof Tier) {
                        throw new TierException("A tier must return either a responsebody, a new Tier or an Array of Tiers");
                    }
                    $this->tiers[] = $tier;
                }
            } // Otherwise it's an error
            else if ($result == false) {
                // The executed tier wasn't able to handle it e.g. a caching layer
                // There should be another tier to execute.
            }
            else {
                throwWrongTypeException($result);
            }
        }

        foreach ($this->postCallables as $postCallable) {
            $this->injector->execute($postCallable);
        }
    }
    
    private function processException(\Exception $e, Request $request)
    {
        ksort($this->exceptionHandlers);
        foreach ($this->exceptionHandlers as $priority => $exceptionHandlerList) {
            foreach ($exceptionHandlerList as $classname => $handler) {
                if ($e instanceof $classname) {
                    $handler($e, $request);
                    return;
                }
            }
        }
        
        
        
        //No exception handlers matched. Lets use the default one.
        processException($e, $request);
    }
}
