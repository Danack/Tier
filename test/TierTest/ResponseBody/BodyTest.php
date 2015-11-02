<?php

namespace TierTest;

use Tier\ResponseBody\ExceptionHtmlBody;

class BodyTest extends BaseTestCase
{
    function testPath()
    {
        $body = new ExceptionHtmlBody("TestMessage");
        $headers = $body->getHeaders();
        ob_start();
        $body->__invoke();
        $text = ob_get_contents();
        ob_end_clean();

        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertArrayHasKey('Content-Length', $headers);

        $this->assertContains("TestMessage", $text);
    }
}
