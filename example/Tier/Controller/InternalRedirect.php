<?php

namespace Tier\Controller;

use Tier\Tier;
use Tier\InjectionParams;

use Tier\Model\RunTimeData;


class InternalRedirect {
    public function firstCall()
    {
        $fn = function() {
            return new RunTimeData("script was called at ".date('l jS \of F Y h:i:s A'));
        };
        
        $params = new InjectionParams();

        $params->delegate(
            'Tier\Model\RunTimeData',
            $fn
        );

        return new Tier('Tier\Controller\InternalRedirect::secondCall', $params);
    }
    
    public function secondCall(RunTimeData $runTimeData)
    {
        //Do something with $runTimeData..
        $runTimeData->someVarThatIsDeterminedAtRuntime .= " and has been touched by the secondCall method.";

        return getTemplateCallable('pages/internalRedirect', ['Tier\Model\RunTimeData' => $runTimeData]);
    }
}