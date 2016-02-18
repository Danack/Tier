<?php

namespace TierTest;

use Tier\HTTPFunction;
use Tier\TierFunction;
use Room11\HTTP\Body\TextBody;
use Room11\HTTP\Request\CLIRequest;
use Room11\HTTP\HeadersSet;
use TierTest\TextEmitter;

class TierTest extends BaseTestCase
{
    /**
     * This is weak sauce. Their is very little testing of the behaviour,
     * but maybe that needs to be done as an integration test anyway.
     */
    public function testSendBodyResponse()
    {
        $bodyString = "Hello world";
        $body = new TextBody($bodyString);
        $request = new CLIRequest("/", "example.com");
        $headersSet = new HeadersSet();
        $emitter = new TextEmitter();

        ob_start();
        HTTPFunction::sendBodyResponse($body, $request, $headersSet, $emitter);
        $contents = ob_get_contents();
        ob_end_clean();
        
        $this->assertEquals($bodyString, $contents);
    }

    public function testExceptionToString()
    {
        $exception = new \Exception("This is a testException");
        $string = TierFunction::getExceptionString($exception);
        //TODO - some assertions.

        if (class_exists('\EngineException', false) === true) {
            try {
                $foo = new \NonExistentClass();
            }
            catch (\Throwable $t) {
                $string = TierFunction::getExceptionString($exception);
            }
        }
    }
}
