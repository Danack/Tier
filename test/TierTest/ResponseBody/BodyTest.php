<?php

namespace TierTest;

use Tier\Body\ExceptionHtmlBody;

class BodyTest extends BaseTestCase
{
    public function testPath()
    {
        $body = new ExceptionHtmlBody("TestMessage", 501);
        $headersSet = $body->getHeadersSet();
        $text = $body->getData();
        $this->assertTrue($headersSet->hasHeaders('Content-Type'));
        $this->assertTrue($headersSet->hasHeaders('Content-Length'));
        //$this->assertArrayHasKey('Content-Length', $headers);

        $this->assertContains("TestMessage", $text);
    }
}
