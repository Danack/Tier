<?php


namespace Tier\Model;

class Color
{
    private $name;
    private $hexColor;
    
    public function __construct($name, $hexColor)
    {
        $this->name = $name;
        $this->hexColor = $hexColor;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getHexColor()
    {
        return $this->hexColor;
    }    
}
