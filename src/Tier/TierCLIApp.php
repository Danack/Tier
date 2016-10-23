<?php

namespace Tier;

use Auryn\Injector;
use AurynConfig\InjectionParams;

use Tier\Callback\NullCallback;

/**
 * Class TierCLIApp
 *
 */
class TierCLIApp extends TierApp
{
    /** @var OutputBufferCleaner(); */
    private $outputBufferCleaner;

    /**
     * @param \AurynConfig\InjectionParams $injectionParams
     * @param Injector $injector
     */
    public function __construct(Injector $injector)
    {
        parent::__construct(
            $injector,
            new NullCallback()
        );
    }

    public function execute()
    {
        try {
            $this->outputBufferCleaner = new OutputBufferCleaner();
            $this->executeInternal();
        }
        finally {
            $this->outputBufferCleaner->checkOutputBufferCleared();
        }
    }
}
