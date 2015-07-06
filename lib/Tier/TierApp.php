<?php


namespace Tier;

use Auryn\Injector;
use Arya\Request;
use Arya\Response;
use Arya\Body as ResponseBody;


function addInjectionParams(Injector $injector, Tier $tier)
{
    $injectionParams = $tier->getInjectionParams();

    if (!$injectionParams) {
        return;
    }
        
    foreach ($injectionParams->getAliases() as $original => $alias) {
        $injector->alias($original, $alias);
    }
    
    foreach ($injectionParams->getShares() as $share) {
        $injector->share($share);
    }
    
    foreach ($injectionParams->getParams() as $paramName => $value) {
        $injector->defineParam($paramName, $value);
    }
    
    foreach ($injectionParams->getDelegates() as $className => $callable) {
        $injector->delegate($className, $callable);
    }
}

function throwWrongTypeException($result) {

    if ($result === null) {
        throw new TierException("Return value of tier must be either a response or a tier, null given.");
    }

    if (is_object($result)) {
        $detail = "object of type ".get_class($result)." returned.";
    }
    else {
        $detail = "Variable of type ".gettype($result)."  returned.";
    }

    $message = sprintf(
        "Return value of tier must be either a response or a tier, instead '%s' returned.'",
        $detail
    );

    throw new TierException($message);
}



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
        $injector = new Injector();
        $injector->share($injector); //yolo
        $response = new Response;
        $injector->share($request);
        $injector->share($response);

        $tier = $this->initialTier;

        $count = 0;
        $responseBody = null;

        while (true) {
            $count++;
            if ($count > $this->MAX_INTERNAL_EXECUTES) {
                throw new TierException("Too many internal executes");
            }

            addInjectionParams($injector, $tier);

            $setupCallable = $tier->getSetupCallable();

            if ($setupCallable) {
                $injector->execute($setupCallable);
            }

            $callable = $tier->getTierCallable();
            $result = $injector->execute($callable);

            if ($result instanceof ResponseBody) {
                $responseBody = $result;
                $response->setBody($responseBody);
                sendResponse($request, $response);
                return;
                break;
            }
            else if ($result instanceof Tier) {
                $tier = $result;
            }
            else {
                throwWrongTypeException($result);
            }
        }
    }
}

