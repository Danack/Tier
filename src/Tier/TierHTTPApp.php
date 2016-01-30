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
    const TIER_INITIAL = 10;

    const TIER_BEFORE_BODY = 20;
    const TIER_GENERATE_BODY = 30;
    const TIER_AFTER_BODY = 40;

    const TIER_BEFORE_RESPONSE = 50;
    const TIER_GENERATE_RESPONSE = 60;
    const TIER_AFTER_RESPONSE = 70;

    const TIER_BEFORE_SEND = 80;
    const TIER_SEND = 90;
    const TIER_AFTER_SEND = 100;

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
        $this->executableListByTier->addExecutable(TierHTTPApp::TIER_INITIAL, $callable);
    }
    
    /**
     * Add a tier to be called before the body is generated.
     * @param $callable
     */
    public function addBeforeGenerateBodyExecutable($callable)
    {
        $this->executableListByTier->addExecutable(TierHTTPApp::TIER_BEFORE_BODY, $callable);
    }

    /**
     * @param Executable $executable
     */
    public function addGenerateBodyExecutable($executable)
    {
        $this->executableListByTier->addExecutable(TierHTTPApp::TIER_GENERATE_BODY, $executable);
    }

    /**
     * Add a tier to be called before the body is generated.
     * @param $callable
     */
    public function addAfterGenerateBodyExecutable($callable)
    {
        $this->executableListByTier->addExecutable(TierHTTPApp::TIER_AFTER_BODY, $callable);
    }

    /**
     * @param $executable
     */
    public function addBeforeGenerateResponseExecutable($executable)
    {
        $this->executableListByTier->addExecutable(TierHTTPApp::TIER_BEFORE_RESPONSE, $executable);
    }

    /**
     * @param $executable
     */
    public function addGenerateResponseExecutable($executable)
    {
        $this->executableListByTier->addExecutable(TierHTTPApp::TIER_GENERATE_RESPONSE, $executable);
    }

    /**
     * @param $executable
     */
    public function addAfterGenerateResponseExecutable($executable)
    {
        $this->executableListByTier->addExecutable(TierHTTPApp::TIER_AFTER_RESPONSE, $executable);
    }

    /**
     * @param $executable
     */
    public function addBeforeSendExecutable($executable)
    {
        $this->executableListByTier->addExecutable(TierHTTPApp::TIER_BEFORE_SEND, $executable);
    }

    /**
     * @param $executable
     */
    public function addSendExecutable($executable)
    {
        $this->executableListByTier->addExecutable(TierHTTPApp::TIER_SEND, $executable);
    }

    /**
     * @param $executable
     */
    public function addAfterSendExecutable($executable)
    {
        $this->executableListByTier->addExecutable(TierHTTPApp::TIER_AFTER_SEND, $executable);
    }
    
    /**
     * @param Request $request
     */
    public function execute(Request $request)
    {
        try {
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
            // The exception handler function also threw? Just exit.
            //Fatal error shutdown
            echo $e->getMessage();
            exit(-1);
        }
        catch (\Throwable $e) {
            //Fatal error shutdown
            echo $e->getMessage();
            exit(-1);
        }
    }
}
