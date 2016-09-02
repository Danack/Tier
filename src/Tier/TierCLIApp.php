<?php

namespace Tier;

use Auryn\Injector;

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
     * @param InjectionParams $injectionParams
     * @param Injector $injector
     * @param ExceptionResolver $exceptionResolver
     */
    public function __construct(Injector $injector) {
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
