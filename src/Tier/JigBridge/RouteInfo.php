<?php

namespace Tier\JigBridge;

class RouteInfo
{
    public $params;
    
    public function __construct($params)
    {
        $this->params = $params;
    }
}
