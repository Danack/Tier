<?php

namespace Tier\Bridge;

/**
 * Class RouteParams
 * A 'context' object that holds any params that are extracted from a matched route
 * during a HTTP request.
 */
class RouteParams
{
    public $params;

    public function __construct($params)
    {
        $this->params = $params;
    }
}
