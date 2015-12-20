<?php

namespace TierTest;

class PathTest extends BaseTestCase
{
    public function testPath()
    {
        $pathValue = "/foo/bar";
        $path = new \Tier\Path\Path($pathValue);
        $this->assertEquals($pathValue, $path->getPath());
    }
    
    public function testPathError()
    {
        $this->setExpectedException('Tier\TierException');
        new \Tier\Path\Path(null);
    }
}
