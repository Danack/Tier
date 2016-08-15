<?php

namespace Tier;

/**
 * Class ExecutableListByTier
 *
 * Holds each of the ExecutableList that needs to be executed by
 * each tier of the application.
 *
 */
class ExecutableByTier implements \Iterator
{
    private $currentTier = -1;
    
    const TIER_NUMBER_LIMIT = 1000000;

    /**
     * @var Executable[]
     */
    private $executableByTier;

    public function __construct()
    {
        $this->currentTier = -1;
    }

    /**
     * @return Executable
     */
    public function current()
    {
        return $this->executableByTier[$this->currentTier];
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
        foreach ($this->executableByTier as $stage => $tiers) {
            if ($stage > $this->currentTier) {
                $this->currentTier = $stage;
                return;
            }
        }

        $this->currentTier = self::TIER_NUMBER_LIMIT;
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
        return ($this->currentTier < self::TIER_NUMBER_LIMIT);
    }

    /**
     * @param $tierOrder
     * @param $executable Executable|callable
     */
    public function addExecutableToTier($tierOrder, $callableOrExecutable)
    {
        if (isset($this->executableByTier[$tierOrder]) === true) {
            throw new TierException("Executable already set for tier $tierOrder");
        }

        if ($tierOrder >= self::TIER_NUMBER_LIMIT) {
            $message = sprintf(
                "Cannot add tier past ExecutableListByTier::TIER_NUMBER_LIMIT which is %d",
                self::TIER_NUMBER_LIMIT
            );
            throw new TierException($message, TierException::INCORRECT_VALUE);
        }

        if (is_a($callableOrExecutable, 'Tier\Executable') === true) {
            $executable = $callableOrExecutable;
        }
        else if (is_callable($callableOrExecutable) === true) {
            $executable = new Executable($callableOrExecutable);
            $executable->setAllowedToReturnNull(true);
        }
        else {
            $message = sprintf(
                'Executable or callable required, instead have: %s',
                substr(var_export($callableOrExecutable, true), 0, 50)
            );
            
            throw new TierException($message);
        }



        $this->executableByTier[$tierOrder] = $executable;
        ksort($this->executableByTier);
    }

    /**
     * Add an executable in the tier it wants to be run in, or the
     * next stage if no tier is set.
     * @param Executable $executable
     * @throws TierException
     */
    public function addExecutable(Executable $executable)
    {
        $tierNumber = $executable->getTierNumber();
        $nextStage = $this->currentTier + 1;

        if ($tierNumber === null) {
            $tierNumber = $nextStage;
        }

        if ($tierNumber < $this->currentTier) {
            $message = sprintf(
                "Cannot add executable to tier %d as current tier is %d",
                $tierNumber,
                $this->currentTier
            );
            throw new TierException($message, TierException::INCORRECT_VALUE);
        }

        $this->addExecutableToTier($tierNumber, $executable);
    }
}
