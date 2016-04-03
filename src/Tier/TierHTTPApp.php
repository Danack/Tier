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
    // The numerical order of tiers. These values should be separated by
    // at least self::$internalExecutions
    const TIER_INITIAL = 100;
    
    const TIER_BEFORE_ROUTING = 200;
    const TIER_ROUTING = 300;
    const TIER_AFTER_ROUTING = 400;

    const TIER_BEFORE_BODY = 500;
    const TIER_GENERATE_BODY = 600;
    const TIER_AFTER_BODY = 700;

    const TIER_BEFORE_RESPONSE = 800;
    const TIER_GENERATE_RESPONSE = 900;
    const TIER_AFTER_RESPONSE = 1000;

    const TIER_BEFORE_SEND = 1100;
    const TIER_SEND = 1200;
    const TIER_AFTER_SEND = 1300;

    /** @var OutputBufferCleaner(); */
    private $outputBufferCleaner;
    
    /** @var ExceptionResolver */
    protected $exceptionResolver;

    /**
     * @param Injector $injector
     * @param ExceptionResolver $exceptionResolver
     */
    public function __construct(
        Injector $injector,
        ExceptionResolver $exceptionResolver = null
    ) {
        parent::__construct($injector, new MaxLoopCallback());
        if ($exceptionResolver === null) {
            $exceptionResolver = $this->createStandardExceptionResolver();
        }
        $this->exceptionResolver = $exceptionResolver;
    }

    /**
     * Create an ExceptionResolver and attach a set of useful exception handlers
     * for HTTP apps.
     * @return ExceptionResolver
     * @throws TierException
     */
    public function createStandardExceptionResolver()
    {
        $exceptionResolver = new ExceptionResolver();
        // Create the exception handlers. More generic exceptions
        // are placed later in the order, so as to allow the more
        // specific exception handlers to handle exceptions.
        $exceptionResolver->addExceptionHandler(
            'Jig\JigException',
            ['Tier\HTTPFunction', 'processJigException'],
            ExceptionResolver::ORDER_MIDDLE
        );
        $exceptionResolver->addExceptionHandler(
            'Auryn\InjectionException',
            ['Tier\HTTPFunction', 'processInjectionException'],
            ExceptionResolver::ORDER_MIDDLE
        );
        $exceptionResolver->addExceptionHandler(
            'Auryn\InjectorException',
            ['Tier\HTTPFunction', 'processInjectorException'],
            ExceptionResolver::ORDER_LAST - 2
        );

        // This will only be triggered on PHP 7
        $exceptionResolver->addExceptionHandler(
            'Throwable',
            ['Tier\HTTPFunction', 'processThrowable'],
            ExceptionResolver::ORDER_LAST
        );
        // This will only be triggered on PHP 5.6
        $exceptionResolver->addExceptionHandler(
            'Exception',
            ['Tier\HTTPFunction', 'processException'],
            ExceptionResolver::ORDER_LAST
        );

        return $exceptionResolver;
    }

    /**
     * @param $callable
     */
    public function addInitialExecutable($callable)
    {
        $this->executableListByTier->addExecutableToTier(TierHTTPApp::TIER_INITIAL, $callable);
    }

    public function addBeforeRoutingExecutable($executable)
    {
        $this->executableListByTier->addExecutableToTier(TierHTTPApp::TIER_BEFORE_ROUTING, $executable);
    }
    
    public function addRoutingExecutable($executable)
    {
        $this->executableListByTier->addExecutableToTier(TierHTTPApp::TIER_ROUTING, $executable);
    }
    
    public function addAfterRoutingExecutable($executable)
    {
        $this->executableListByTier->addExecutableToTier(TierHTTPApp::TIER_AFTER_ROUTING, $executable);
    }
    
    
    /**
     * Add a tier to be called before the body is generated.
     * @param $callable
     */
    public function addBeforeGenerateBodyExecutable($callable)
    {
        $this->executableListByTier->addExecutableToTier(TierHTTPApp::TIER_BEFORE_BODY, $callable);
    }

    /**
     * @param Executable $executable
     */
    public function addGenerateBodyExecutable($executable)
    {
        $this->executableListByTier->addExecutableToTier(TierHTTPApp::TIER_GENERATE_BODY, $executable);
    }

    /**
     * Add a tier to be called before the body is generated.
     * @param $callable
     */
    public function addAfterGenerateBodyExecutable($callable)
    {
        $this->executableListByTier->addExecutableToTier(TierHTTPApp::TIER_AFTER_BODY, $callable);
    }

    /**
     * @param $executable
     */
    public function addBeforeGenerateResponseExecutable($executable)
    {
        $this->executableListByTier->addExecutableToTier(TierHTTPApp::TIER_BEFORE_RESPONSE, $executable);
    }

    /**
     * @param $executable
     */
    public function addGenerateResponseExecutable($executable)
    {
        $this->executableListByTier->addExecutableToTier(TierHTTPApp::TIER_GENERATE_RESPONSE, $executable);
    }

    /**
     * @param $executable
     */
    public function addAfterGenerateResponseExecutable($executable)
    {
        $this->executableListByTier->addExecutableToTier(TierHTTPApp::TIER_AFTER_RESPONSE, $executable);
    }

    /**
     * @param $executable
     */
    public function addBeforeSendExecutable($executable)
    {
        $this->executableListByTier->addExecutableToTier(TierHTTPApp::TIER_BEFORE_SEND, $executable);
    }

    /**
     * @param $executable
     */
    public function addSendExecutable($executable)
    {
        $this->executableListByTier->addExecutableToTier(TierHTTPApp::TIER_SEND, $executable);
    }

    /**
     * @param $executable
     */
    public function addAfterSendExecutable($executable)
    {
        $this->executableListByTier->addExecutableToTier(TierHTTPApp::TIER_AFTER_SEND, $executable);
    }
    
    /**
     * @param Request $request
     */
    public function execute(Request $request)
    {
        $this->outputBufferCleaner = new OutputBufferCleaner();

        try {
            $this->injector->alias(
                'Psr\Http\Message\ServerRequestInterface',
                get_class($request)
            );
            $this->injector->share($request);
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
            $fallBackHandler = ['Tier\Tier', 'handleThrowable'];
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
