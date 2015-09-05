<?php

namespace Tier\VariableMap;

class ArrayVariableMap implements VariableMap
{
    public function __construct(array $variables)
    {
        $this->variables = $variables;
    }

    public function getVariable($variableName, $default = false, $minimum = false, $maximum = false)
    {
        if (array_key_exists($variableName, $this->variables) == true) {
            $result = $this->variables[$variableName];
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
