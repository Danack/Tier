<?php

namespace TierTest;

use Tier\TierApp;
use Tier\InjectionParams;


class TierAppTest extends BaseTestCase
{
    function testPath()
    {
        $injectionParams = new InjectionParams();
        $tierApp = new TierApp($injectionParams);
        
        $tierApp->addExpectedProduct('Fixtures\Body');
        $tierApp->addTier()
        
    }

}
