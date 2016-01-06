<?php

namespace Tier;

/**
 * Class ExecutableListByTier
 *
 * Holds each of the ExecutableList that needs to be executed by
 * each tier of the application.
 *
 */
class ExecutableListByTier implements \Iterator
{
    private $currentTier = -1;

    /**
     * @var ExecutableList[]
     */
    private $executableListByTier;

    public function __construct()
    {
        $this->currentTier = -1;
    }

    /**
     * @return ExecutableList
     */
    public function current()
    {
        return $this->executableListByTier[$this->currentTier];
    }

    /**
     * @return int
     */
    public function key()
    {
        return $this->currentTier;
    }

    /**
     *
     */
    public function next()
    {
        //The stages are already sorted by key value
        foreach ($this->executableListByTier as $stage => $tiers) {
            if ($stage > $this->currentTier) {
                $this->currentTier = $stage;
                return;
            }
        }

        $this->currentTier = 100000000;
    }

    /**
     *
     */
    public function rewind()
    {
        $this->currentTier = -1;
        $this->next();
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return ($this->currentTier < 1000);
    }

    /**
     * @param $tierOrder
     * @param $executable Executable|callable
     */
    public function addExecutable($tierOrder, $executable)
    {
        if (isset($this->executableListByTier[$tierOrder]) === false) {
            $this->executableListByTier[$tierOrder] = new ExecutableList();
        }
        
        $this->executableListByTier[$tierOrder]->addExecutable($executable);
        ksort($this->executableListByTier);
    }

    /**
     * @param $tier
     */
    public function addNextStageTier($tier)
    {
        $nextStage = $this->currentTier + 1;
        $this->addExecutable($nextStage, $tier);
    }
}
