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

    public function __construct(Tier $tier, InjectionParams $injectionParams = null)
    {
        $this->tiers[] = $tier;
        $this->initialInjectionParams = $injectionParams;
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

    public function execute(Request $request)
    {
        try {
            $this->executeInternal($request);
        }
        catch (InjectionException $ie) {
            // TODO - add custom notifications.
        
            $body = $ie->getMessage();
            $body .= implode("<br/>", $ie->getDependencyChain());
        
            $body = new ExceptionHtmlBody($body);
            \Tier\sendErrorResponse($request, $body, 500);
        }
        catch (InjectorException $ie) {
            // TODO - add custom notifications.
        
            $body = new ExceptionHtmlBody($ie);
            \Tier\sendErrorResponse($request, $body, 500);
        }
        
        catch (JigException $je) {
            $body = new ExceptionHtmlBody($je);
            \Tier\sendErrorResponse($request, $body, 500);
        }
        catch (\Exception $e) {
            $body = new ExceptionHtmlBody($e);
            \Tier\sendErrorResponse($request, $body, 500);
        }
    }
    
    
    public function executeInternal(Request $request)
    {
        // Create and share these as they need to be the same
        // across the application
        $injector = new Injector();
        $response = new Response;
        $injector->share($request);
        $injector->share($response);
        $injector->share($injector); //yolo

        $this->initialInjectionParams->addToInjector($injector);

        foreach ($this->preCallables as $preCallable) {
            $injector->execute($preCallable);
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
                $injectionParams->addToInjector($injector);
            }

            // If the next Tier has a setup function, call it
            $setupCallable = $tier->getSetupCallable();
            if ($setupCallable) {
                $injector->execute($setupCallable);
            }

            // Call this Tier's callable
            $result = $injector->execute($tier->getTierCallable());

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
            $injector->execute($postCallable);
        }
    }
}
