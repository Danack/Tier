<?php


namespace TierTest\Integration;

use Tier\InjectionParams;
use Tier\TierHTTPApp;
use Room11\HTTP\Request\CLIRequest;
use Tier\TierApp;
use TierTest\BaseTestCase;
use Auryn\Injector;

class IntegrationTest extends BaseTestCase
{
    /** @var  InjectionParams */
    private $injectionParams;
    
    /** @var  \Auryn\Injector */
    private $injector;
    
    public function setup()
    {
        parent::setup();
//        $shares = [];
//        $aliases = [];
//        $delegates = [];
//        $params = [];
//        $prepares = [];
//        $defines = [];
//
//        $this->injectionParams = new InjectionParams(
//            $shares,
//            $aliases,
//            $delegates,
//            $params,
//            $prepares,
//            $defines
//        );
//        
//        $this->injector = new Injector();
//        $this->injectionParams->addToInjector($this->injector);
    }

    public function testExecutableOrder()
    {
        // Create the Tier application
        $app = new TierHTTPApp(new Injector());
        $callCount = 0;

        $fn = function ($stage, $expectedCallCount, $returnValue) use (&$callCount) {
            return function () use (&$callCount, $stage, $expectedCallCount, $returnValue) {
                if ($callCount !== $expectedCallCount) {
                    throw new \Exception("Wrong call count for $stage");
                }
                $callCount++;
                
                return $returnValue;
            };
        };
        
        $app->addInitialExecutable($fn('Initial', 0, TierApp::PROCESS_CONTINUE));

        $app->addBeforeGenerateBodyExecutable($fn('BeforeGenerate', 1, TierApp::PROCESS_CONTINUE));
        $app->addGenerateBodyExecutable($fn('GenerateBody', 2, TierApp::PROCESS_CONTINUE));
        $app->addAfterGenerateBodyExecutable($fn('AfterGenerateBody', 3, TierApp::PROCESS_CONTINUE));
        
        $app->addBeforeGenerateResponseExecutable($fn('BeforeGenerate', 4, TierApp::PROCESS_CONTINUE));
        $app->addGenerateResponseExecutable($fn('GenerateResponse', 5, TierApp::PROCESS_CONTINUE));
        $app->addAfterGenerateResponseExecutable($fn('AfterGenerate', 6, TierApp::PROCESS_CONTINUE));
    
        $app->addBeforeSendExecutable($fn('BeforeSend', 7, TierApp::PROCESS_CONTINUE));
        $app->addSendExecutable($fn('Send', 8, TierApp::PROCESS_CONTINUE));
        $app->addAfterSendExecutable($fn('After', 9, TierApp::PROCESS_END));

        $request = new CLIRequest('/', "example.com");

        $app->execute($request);
    }
}
