<?php


namespace Tier;

class Executables implements \Iterator 
{
    private $position = 0;

    /**
     * @var Tier[]
     */
    private $stages;

    public function __construct()
    {
        $this->position = 0;
    }

    public function current()
    {
        return $this->stages[$this->position];
    }
    
    public function key() {
        return $this->position;
    }
    
    public function next ()
    {
        //The stages are already sorted by key value
        $this->position++;
    }
    
    public function rewind()
    {
        $this->position = 0;
    }
    
    public function valid() {
        return isset($this->stages[$this->position]);
    }

    public function addTier($tier)
    {
        $this->stages[] = $tier;
    }
}
