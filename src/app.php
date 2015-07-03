<?php

use Tier\Tier;
use Tier\Response\Response;
use Tier\Response\TextResponse;


require_once "../src/bootstrap.php";

$injector = bootstrapInjector();

$callable = 'getRouteCallable';


$obj = $injector->make('GithubService\GithubArtaxService\GithubService');



try {
    $count = 0;
    
    do {
        $result = $injector->execute($callable);
    
        if ($result instanceof Response) {
            $result->send();
            break;
        }
        else if ($result instanceof Tier) {
            addInjectionParams($injector, $result);
            $callable = $result->getCallable();
        }
        else if ($result === null) {
            throw new \Exception("Return value of tier must be either a response or a tier, null given.");
        }
        else {
            throw new \Exception("Return value of tier must be either a response or a tier, instead ".get_class($result).' returned.');
        }
        
        $count++;
    } while ($count < 10);
}
catch(Jig\JigException $je) {
    echo "Error rendering template: ".$je->getMessage()."<br/>";
    echo nl2br($je->getTraceAsString());    
}
catch(\Exception $e) {
    echo "Unexpected exception: " .$e->getMessage()."<br/>";
    echo nl2br($e->getTraceAsString());
}

