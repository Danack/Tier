<?php

namespace Tier\VariableMap;

interface VariableMap
{
    public function getVariable($variableName, $default = false, $minimum = false, $maximum = false);
}
