<?php

namespace Tier\Model;


class User
{
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}
