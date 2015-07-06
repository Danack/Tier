<?php


namespace Tier;

use Auryn\Injector;
use Arya\Request;
use Arya\Response;
use Arya\Body as ResponseBody;






class TierApp {

    /**
     * @var Tier
     */
    private $initialTier;

    public $MAX_INTERNAL_EXECUTES = 10;

    public function __construct(Tier $tier)
    {
        $this->initialTier = $tier;
    }

    public function execute(Request $request)
    {
        // Create and share these as they need to be the same
        // across the application
        $injector = new Injector();
        $response = new Response;
        $injector->share($request);
        $injector->share($response);
        $injector->share($injector); //yolo

        $tier = $this->initialTier;

        $count = 0;
        $responseBody = null;

        while (true) {
            //Check we haven't got caught in a redirect loop
            $count++;
            if ($count > $this->MAX_INTERNAL_EXECUTES) {
                throw new TierException("Too many internal executes");
            }

            // Setup the information created by the previous Tier
            addInjectionParams($injector, $tier);
            
            // If the next Tier has a setup function, call it
            $setupCallable = $tier->getSetupCallable();
            if ($setupCallable) {
                $injector->execute($setupCallable);
            }

            // Call this Tier's callable
            $result = $injector->execute($tier->getTierCallable());
            
            // If it's a responseBody send it
            if ($result instanceof ResponseBody) {
                $responseBody = $result;
                $response->setBody($responseBody);
                sendResponse($request, $response);
                return;
            }
            // If it's a new Tier to run, setup the next loop.
            else if ($result instanceof Tier) {
                $tier = $result;
            }
            // Otherwise it's an error
            else {
                throwWrongTypeException($result);
            }
        }
    }
}

