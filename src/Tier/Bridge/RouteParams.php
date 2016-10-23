<?php

namespace Tier\Bridge;

/**
 * Class RouteParams
 * A 'context' object that holds any params that are extracted from a matched route
 * during a HTTP request.
 */
class RouteParams
{
    private $params;

    public function __construct($params)
    {
        $this->params = $params;
    }

    public function hasParam($variableName)
    {
        return array_key_exists($variableName, $this->params);
    }

    /**
     * @inheritdoc
     */
    public function getVariable($variableName, $default = false, $minimum = false, $maximum = false)
    {
        if (array_key_exists($variableName, $this->params) === true) {
            $result = $this->params[$variableName];
        }
        else {
            $result = $default;
        }

        if ($minimum !== false) {
            if ($result < $minimum) {
                $result = $minimum;
            }
        }

        if ($maximum !== false) {
            if ($result > $maximum) {
                $result = $maximum;
            }
        }

        return $result;
    }
}
