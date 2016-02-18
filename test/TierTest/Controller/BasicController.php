<?php

namespace TierTest\Controller;

use Tier\Bridge\JigExecutable;
use Tier\Bridge\TierJig;
use Room11\HTTP\Body\TextBody;
use Fixtures\UnknownInterface;
use Tier\TierFunction;
use Tier\TierApp;

class BasicController
{
    public static $notShownText = 'Not shown text';
    
    
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
    
    
    public function testOutputBufferingIsCleared()
    {
        ob_start();
        echo \TierTest\Controller\BasicController::$notShownText;

        throw new \Exception("Throwing exception");
    }

    public function renderTemplateExecutable()
    {
        return JigExecutable::create('pages/renderTemplateExecutable');
    }
}
