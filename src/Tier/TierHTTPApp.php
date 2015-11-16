<?php


namespace Tier;

use Auryn\Injector;
use Room11\HTTP\Request;

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
    const TIER_BEFORE_SEND = 40;
    const TIER_SEND = 50;
    const TIER_AFTER_SEND = 60;

    /**
     * @var ExceptionResolver
     */
    protected $exceptionResolver;
    
    public function __construct(
        InjectionParams $injectionParams,
        Injector $injector = null,
        ExceptionResolver $exceptionResolver = null
    ) {
        parent::__construct($injectionParams, $injector);
            
        if ($exceptionResolver == null) {
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
        $exceptionResolver->addExceptionHandler(
            'Jig\JigException',
            'Tier\processJigException',
            ExceptionResolver::ORDER_MIDDLE
        );
        $exceptionResolver->addExceptionHandler(
            'Auryn\InjectorException',
            'Tier\processInjectorException',
            ExceptionResolver::ORDER_LAST - 2
        );
        $exceptionResolver->addExceptionHandler(
            'Auryn\InjectionException',
            'Tier\processInjectionException',
            ExceptionResolver::ORDER_MIDDLE
        );
        $exceptionResolver->addExceptionHandler(
            'Exception',
            'Tier\processException',
            ExceptionResolver::ORDER_LAST
        );

        return $exceptionResolver;
    }
    
    /**
     * @param $callable
     */
    public function addPreCallable($callable)
    {
        $this->executableListByTier->addExecutable(TierHTTPApp::TIER_BEFORE_BODY, $callable);
    }

    /**
     * @param Executable $tier
     */
    public function addGenerateBodyExecutable(Executable $tier)
    {
        $this->executableListByTier->addExecutable(TierHTTPApp::TIER_GENERATE_BODY, $tier);
    }

    /**
     * @param $callable
     */
    public function addSendCallable($callable)
    {
        $this->executableListByTier->addExecutable(TierHTTPApp::TIER_SEND, $callable);
    }

    /**
     * @param $callable
     */
    public function addBeforeSendCallable($callable)
    {
        $this->executableListByTier->addExecutable(TierHTTPApp::TIER_BEFORE_SEND, $callable);
    }

    /**
     * @param $callable
     */
    public function addPostCallable($callable)
    {
        $this->executableListByTier->addExecutable(TierHTTPApp::TIER_AFTER_SEND, $callable);
    }
    
        /**
     * @param Request $request
     */
    public function execute(Request $request)
    {
        try {
            $this->injector->alias('Room11\HTTP\Request', get_class($request));
            $this->injector->share($request);
            $this->executeInternal();
        }
        catch (\Exception $e) {
            list($handler, $exceptionClass) = $this->exceptionResolver->getExceptionHandler(
                $e,
                'processException'
            );

            $injector = clone $this->injector;
            if (strcasecmp($exceptionClass, get_class($e)) !== 0) {
                $injector->alias($exceptionClass, get_class($e));
            }
            $injector->share($e);
            $injector->execute($handler);
        }
    }
}
