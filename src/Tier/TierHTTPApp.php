<?php


namespace Tier;

use Auryn\Injector;
use Psr\Http\Message\ServerRequestInterface as Request;
use Tier\Context\ExceptionContext;

/**
 * Class TierHTTPApp
 *
 * None of the 'callable' parameters can have a 'callable' type as Tier also supports
 * instance methods (e.g. 'Foo::bar') but these do not pass the callable param test.
 * @package Tier
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

    /**
     * @var ExceptionResolver
     */
    protected $exceptionResolver;

    /**
     * @param InjectionParams $injectionParams
     * @param Injector $injector
     * @param ExceptionResolver $exceptionResolver
     */
    public function __construct(
        InjectionParams $injectionParams,
        Injector $injector = null,
        ExceptionResolver $exceptionResolver = null
    ) {
        parent::__construct($injectionParams, $injector);
            
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
            ['Tier\Tier', 'processJigException'],
            ExceptionResolver::ORDER_MIDDLE
        );
        $exceptionResolver->addExceptionHandler(
            'Auryn\InjectionException',
            ['Tier\Tier', 'processInjectionException'],
            ExceptionResolver::ORDER_MIDDLE
        );
        $exceptionResolver->addExceptionHandler(
            'Auryn\InjectorException',
            ['Tier\Tier', 'processInjectorException'],
            ExceptionResolver::ORDER_LAST - 2
        );

        // This will only be triggered on PHP 7
        $exceptionResolver->addExceptionHandler(
            'Throwable',
            ['Tier\Tier', 'processThrowable'],
            ExceptionResolver::ORDER_LAST
        );
        // This will only be triggered on PHP 5.6
        $exceptionResolver->addExceptionHandler(
            'Exception',
            ['Tier\Tier', 'processException'],
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
        try {
            Tier::$initialOBLevel = ob_get_level();

            $this->injector->alias(
                'Psr\Http\Message\ServerRequestInterface',
                get_class($request)
            );
            $this->injector->share($request);
            $this->executeInternal();
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
        Tier::clearOutputBuffer();
        //TODO - we are now failing. Replace error handler with instant
        //shutdown handler.
        $fallBackHandler = ['Tier\Tier', 'processException'];
        if (class_exists('\Throwable') === true) {
            $fallBackHandler = ['Tier\Tier', 'processThrowable'];
        }

        $handler = $this->exceptionResolver->getExceptionHandler(
            $exception,
            $fallBackHandler
        );

        try {
            call_user_func($handler, $exception);
        }
        catch (\Exception $e) {
            Tier::clearOutputBuffer();
            // The exception handler function also threw? Just exit.
            //Fatal error shutdown
            echo $e->getMessage();
            exit(-1);
        }
        catch (\Throwable $e) {
            Tier::clearOutputBuffer();
            //Fatal error shutdown
            echo $e->getMessage();
            exit(-1);
        }
    }
}
