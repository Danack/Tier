<?php


namespace Tier;

class ExecutablesByStage implements \Iterator
{
    private $currentStage = -1;

    /**
     * @var Executables[]
     */
    private $stages;

    public function __construct()
    {
        $this->currentStage = -1;
    }

    public function current()
    {
        return $this->stages[$this->currentStage];
    }
    
    public function key()
    {
        return $this->currentStage;
    }
    
    public function next()
    {
        //The stages are already sorted by key value
        foreach ($this->stages as $stage => $tiers) {
            if ($stage > $this->currentStage) {
                $this->currentStage = $stage;
                return;
            }
        }

        $this->currentStage = 100000000;
    }
    
    public function rewind()
    {
        $this->currentStage = -1;
        $this->next();
    }
    
    public function valid()
    {
        return ($this->currentStage < 1000);
    }

    public function addTier($stage, $tier)
    {
        if (isset($this->stages[$stage]) == false) {
            $this->stages[$stage] = new Executables();
        }
        
        $this->stages[$stage]->addTier($tier);
        ksort($this->stages);
    }
    
    public function addNextStageTier($tier)
    {
        $nextStage = $this->currentStage + 1;
        $this->addTier($nextStage, $tier);
    }
}
