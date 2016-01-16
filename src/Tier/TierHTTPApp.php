<?php


namespace Tier;

use Auryn\Injector;
use Psr\Http\Message\ServerRequestInterface as Request;

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
        
        $fallbackHandler = ['Tier\Tier', 'processException'];
        // This will only be triggered on PHP 7
        $exceptionResolver->addExceptionHandler(
            'Throwable',
            $fallbackHandler,
            ExceptionResolver::ORDER_LAST
        );
        // This will only be triggered on PHP 5.6
        $exceptionResolver->addExceptionHandler(
            'Exception',
            $fallbackHandler,
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
        list($handler, $exceptionClass) = $this->exceptionResolver->getExceptionHandler(
            $exception,
            'processException'
        );

        $injector = clone $this->injector;
        if (strcasecmp($exceptionClass, get_class($exception)) !== 0) {
            $injector->alias($exceptionClass, get_class($exception));
        }
        $injector->share($exception);
        $injector->execute($handler);
    }
}
