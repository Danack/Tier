<?php

namespace Tier;

class OutputBufferCleaner
{
    private $initialOBLevel;

    public function __construct()
    {
        $this->initialOBLevel = ob_get_level();
    }
    
    public function checkOutputBufferCleared()
    {
        if (ob_get_level() !== $this->initialOBLevel) {
            throw new TierException("Execution of the application resulted in an uncleared output buffer.");
        }
    }

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
