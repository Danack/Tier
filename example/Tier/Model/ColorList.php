<?php

namespace Tier\Model;


class ColorList implements \IteratorAggregate
{
    private $colors = [];

    public function __construct()
    {
        $this->colors[] = new Color("CadetBlue", "#5f9ea0");
        $this->colors[] = new Color("DarkOrchid", "#9932cc");
        $this->colors[] = new Color("gold1", "#ffd700");
        $this->colors[] = new Color("HotPink", "#ff69b4");
    }
    
    public function getIterator() {
        return new \ArrayIterator($this);
    }
    public function getColors()
    {
        return $this->colors;
    }
}
