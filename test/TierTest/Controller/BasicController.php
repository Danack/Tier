<?php

namespace TierTest\Controller;

use Tier\JigBridge\TierJig;

class BasicController
{
    public function introduction(TierJig $tierJig)
    {
        return $tierJig->createJigExecutable('pages/index');
    }
}
