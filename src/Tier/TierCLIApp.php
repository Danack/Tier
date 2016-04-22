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
    // The numerical order of tiers. These values should be separated by
    // at least self::$internalExecutions
    const TIER_INITIAL = 100;
    
    const TIER_BEFORE_ROUTING = 200;
    const TIER_ROUTING = 300;
    const TIER_AFTER_ROUTING = 400;
    
    const TIER_LOOP = 450;

    const TIER_BEFORE_OUTPUT = 500;
    const TIER_GENERATE_OUTPUT = 600;
    const TIER_AFTER_OUTPUT = 700;

    const TIER_BEFORE_CLEANUP = 800;
    const TIER_CLEANUP = 900;
    const TIER_AFTER_CLEANUP = 1000;

    /**
     * @var ExceptionResolver
     */
    protected $exceptionResolver;
    
    
    /** @var OutputBufferCleaner(); */
    private $outputBufferCleaner;

    /**
     * @param InjectionParams $injectionParams
     * @param Injector $injector
     * @param ExceptionResolver $exceptionResolver
     */
    public function __construct(
        Injector $injector,
        ExceptionResolver $exceptionResolver = null
    ) {
        parent::__construct(
            $injector,
            new NullCallback()
        );
            
        if ($exceptionResolver === null) {
            $exceptionResolver = $this->createStandardExceptionResolver();
        }
        $this->exceptionResolver = $exceptionResolver;
    }

    protected function sanityCheckLoopProcesing()
    {
        // Have a default timeout for CLI apps?
        // Otherwise nothing to do.
    }

    /**
     * Create an ExceptionResolver and attach a set of useful exception handlers
     * for HTTP apps.
     * @return ExceptionResolver
     * @throws TierException
     */
    public static function createStandardExceptionResolver()
    {
        $exceptionResolver = new ExceptionResolver();
        // Create the exception handlers. More generic exceptions
        // are placed later in the order, so as to allow the more
        // specific exception handlers to handle exceptions.
        $exceptionResolver->addExceptionHandler(
            'Tier\InvalidReturnException',
            ['Tier\CLIFunction', 'handleInvalidReturnException'],
            ExceptionResolver::ORDER_LAST
        );

        // This will only be triggered on PHP 7
        $exceptionResolver->addExceptionHandler(
            'Throwable',
            ['Tier\CLIFunction', 'handleThrowable'],
            ExceptionResolver::ORDER_LAST
        );

        // This will only be triggered on PHP 5.6
        $exceptionResolver->addExceptionHandler(
            'Exception',
            ['Tier\CLIFunction', 'handleException'],
            ExceptionResolver::ORDER_LAST
        );

        return $exceptionResolver;
    }

    /**
     * @param $callable
     */
    public function addInitialExecutable($callable)
    {
        $this->executableListByTier->addExecutableToTier(self::TIER_INITIAL, $callable);
    }

    public function addBeforeRoutingExecutable($executable)
    {
        $this->executableListByTier->addExecutableToTier(self::TIER_BEFORE_ROUTING, $executable);
    }
    
    public function addRoutingExecutable($executable)
    {
        $this->executableListByTier->addExecutableToTier(self::TIER_ROUTING, $executable);
    }
    
    public function addAfterRoutingExecutable($executable)
    {
        $this->executableListByTier->addExecutableToTier(self::TIER_AFTER_ROUTING, $executable);
    }
    

    /**
     * @param $executable
     */
    public function addBeforeGenerateOutputExecutable($executable)
    {
        $this->executableListByTier->addExecutableToTier(self::TIER_BEFORE_OUTPUT, $executable);
    }

    /**
     * @param $executable
     */
    public function addGenerateOutputExecutable($executable)
    {
        $this->executableListByTier->addExecutableToTier(self::TIER_GENERATE_OUTPUT, $executable);
    }

    /**
     * @param $executable
     */
    public function addAfterGenerateOutputExecutable($executable)
    {
        $this->executableListByTier->addExecutableToTier(self::TIER_AFTER_OUTPUT, $executable);
    }

    /**
     * @param $executable
     */
    public function addBeforeCleanupExecutable($executable)
    {
        $this->executableListByTier->addExecutableToTier(self::TIER_BEFORE_CLEANUP, $executable);
    }
    /**
     * @param $executable
     */
    public function addCleanupExecutable($executable)
    {
        $this->executableListByTier->addExecutableToTier(self::TIER_CLEANUP, $executable);
    }

    /**
     * @param $executable
     */
    public function addAfterCleanupExecutable($executable)
    {
        $this->executableListByTier->addExecutableToTier(self::TIER_AFTER_CLEANUP, $executable);
    }

    public function execute()
    {
        try {
            $this->outputBufferCleaner = new OutputBufferCleaner();
            $this->executeInternal();
            $this->outputBufferCleaner->checkOutputBufferCleared();
        }
        catch (\Throwable $t) {
            $this->processException($t);
        }
        catch (\Exception $e) {
            $this->processException($e);
        }
    }

    /**
     * Actually handle the exception.
     * @param $exception
     */
    private function processException($exception)
    {
        $this->outputBufferCleaner->clearOutputBuffer();
        //TODO - we are now failing. Replace error handler with instant
        //shutdown handler.
        $fallBackHandler = ['Tier\CLIFunction', 'handleException'];
        if (class_exists('Throwable') === true) {
            $fallBackHandler = ['Tier\CLIFunction', 'handleThrowable'];
        }

        $handler = $this->exceptionResolver->getExceptionHandler(
            $exception,
            $fallBackHandler
        );

        try {
            call_user_func($handler, $exception);
        }
        catch (\Exception $e) {
            $this->outputBufferCleaner->clearOutputBuffer();
            // The exception handler function also threw? Just exit.
            //Fatal error shutdown
            echo $e->getMessage();
            exit(-1);
        }
        catch (\Throwable $e) {
            $this->outputBufferCleaner->clearOutputBuffer();
            //Fatal error shutdown
            echo $e->getMessage();
            exit(-1);
        }
    }
}
