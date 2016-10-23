<?php

namespace Tier;

/**
 * Class OutputBufferCleaner
 * Helper class to track output buffer level and clean up to the initial level.
 * This is used to prevent PHP from displaying the contents of the output buffer
 * on any program error.
 */
class OutputBufferCleaner
{
    private $initialOBLevel;

    public function __construct()
    {
        $this->initialOBLevel = ob_get_level();
    }

    /**
     * @throws TierException
     */
    public function checkOutputBufferCleared()
    {
        if (ob_get_level() !== $this->initialOBLevel) {
            $this->clearOutputBuffer();
            throw new TierException("Execution of the application resulted in an uncleared output buffer.");
        }
    }

    /**
     *
     */
    public function clearOutputBuffer()
    {
        //Need to ob_end_clean as many times as required because
        //otherwise partial content gets sent to the client.
        $obClearCount = 0;
        while (ob_get_level() > $this->initialOBLevel) {
            ob_end_clean();
            $obClearCount++;
        }
    }
}
