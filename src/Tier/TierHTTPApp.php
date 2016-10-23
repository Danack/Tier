<?php


namespace Tier;

use Auryn\Injector;
use Psr\Http\Message\ServerRequestInterface as Request;
use Tier\Callback\MaxLoopCallback;

/**
 * Class TierHTTPApp
 *
 * An implementation of a TierApp that is targeted at processing PSR7
 * ServerRequestInterface requests.
 */
class TierHTTPApp extends TierApp
{
    /** @var OutputBufferCleaner(); */
    private $outputBufferCleaner;

    /**
     * @param Injector $injector
     * @param ExceptionResolver $exceptionResolver
     */
    public function __construct(
        Injector $injector,
        ExceptionResolver $exceptionResolver = null
    ) {
        parent::__construct($injector, new MaxLoopCallback());
    }


    /**
     * @param Request $request
     */
    public function execute(Request $request)
    {
        try {
            $this->outputBufferCleaner = new OutputBufferCleaner();
            $this->injector->alias(
                'Psr\Http\Message\ServerRequestInterface',
                get_class($request)
            );
            $this->injector->share($request);
            $this->executeInternal();
            $this->outputBufferCleaner->checkOutputBufferCleared();
        }
        finally {
            $this->outputBufferCleaner->clearOutputBuffer();
        }
    }
}
