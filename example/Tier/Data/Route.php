<?php

namespace Tier\Data;

class Route {

    public $method;
    public $path;
    public $callable;
    public $name;

    public function __construct($method, $path, $callable)
    {
        $this->method = $method;
        $this->path = $path;
        $this->callable = $callable;
    }
}

