<?php


namespace Tier\Body;

use Tier\TierException;

class CallableFileGenerator implements FileGenerator
{
    private $fn;
    
    /** @var int Last modified time in Unix Epoch. */
    private $lastModifiedTime;
    
    public function __construct(callable $fn, $lastModifiedTime)
    {
        $this->fn = $fn;
        $this->lastModifiedTime = $lastModifiedTime;
    }
    
    public function getModifiedTime()
    {
        return $this->lastModifiedTime;
    }

    /**
     * Generate the file and return the filename
     * @return string
     */
    public function generate()
    {
        $callable = $this->fn;
        $name = $callable();
        
        if (is_string($name) === false) {
            throw new TierException("CallableFileGenerator failed to return filename");
        }

        return $name;
    }
}
