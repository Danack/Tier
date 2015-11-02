<?php

namespace TierTest;

class PathTest extends BaseTestCase
{
    function testPath()
    {
        $pathValue = "/foo/bar";
        $path = new \Tier\Path\Path($pathValue);
        $this->assertEquals($pathValue, $path->getPath());
    }
    
    function testPathError()
    {
        $this->setExpectedException('Tier\TierException');
        new \Tier\Path\Path(null);
    }
}
