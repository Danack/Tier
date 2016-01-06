<?php

namespace TierTest;

use Tier\Body\ExceptionHtmlBody;

class BodyTest extends BaseTestCase
{
    public function testPath()
    {
        $body = new ExceptionHtmlBody("TestMessage", 501);
        $headers = $body->getHeaders();
        $text = $body->getData();
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertArrayHasKey('Content-Length', $headers);

        $this->assertContains("TestMessage", $text);
    }
}
