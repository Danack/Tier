<?php

namespace TierTest;

use Tier\ResponseBody\ExceptionHtmlBody;

class BodyTest extends BaseTestCase
{
    public function testPath()
    {
        $body = new ExceptionHtmlBody("TestMessage", 501);
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
