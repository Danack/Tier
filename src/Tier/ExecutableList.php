<?php


namespace Tier;

/**
 * Class ExecutableList
 * 
 * A list of all Executables that need to be executed for an individual tier.
 */
class ExecutableList implements \Iterator
{
    private $position = 0;

    /**
     * @var Executable[]
     */
    private $list;

    public function __construct()
    {
        $this->position = 0;
    }

    public function current()
    {
        return $this->list[$this->position];
    }
    
    public function key()
    {
        return $this->position;
    }
    
    public function next()
    {
        //The stages are already sorted by key value
        $this->position++;
    }
    
    public function rewind()
    {
        $this->position = 0;
    }
    
    public function valid()
    {
        return isset($this->list[$this->position]);
    }

    public function addExecutable($executable)
    {
        $this->list[] = $executable;
    }
}
