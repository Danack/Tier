<?php

namespace TierTest\Controller;

use Tier\JigBridge\TierJig;
use Room11\HTTP\Body\TextBody;
use Fixtures\UnknownInterface;

class BasicController
{
    public function helloWorld()
    {
        return new TextBody("Hello world");
    }
    
    public function instantiateUnknownClass()
    {
        $obj = new ThisClassDoesNotExist();
    }
    
    public function throwException()
    {
        throw new \Exception("Testing exception handler");
    }
    
    public function unknownDependency(UnknownInterface $unknown)
    {
        return new TextBody("This shouldn't be reached.");
    }

    public function introduction(TierJig $tierJig)
    {
        return $tierJig->createJigExecutable('pages/index');
    }
}
