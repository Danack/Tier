<?php

namespace TierTest\ResponseBody;

use Auryn\Injector;
use TierTest\BaseTestCase;
use Jig\JigConfig;
use Jig\Jig;
use FastRoute\RouteCollector;
use Room11\HTTP\Request\CLIRequest;
use Room11\Caching\LastModifiedStrategy;
//use Tier\Body\CachingFileBodyFactory;
use Tier\Body\CachingFileBodyFactory;
use Mockery;
use Room11\HTTP\RequestHeaders\ArrayRequestHeaders;

class CachingFileResponseFactoryTest extends BaseTestCase
{
    public function testBasic()
    {
        $mockStrategy = Mockery::mock('Room11\Caching\LastModifiedStrategy');
        $mockStrategy
            ->shouldReceive('getHeaders')
            ->once()
            ->andReturn([]);
        
        $requestHeaders = new ArrayRequestHeaders([]);
        
        $cachingFileResponse = new CachingFileBodyFactory($requestHeaders, $mockStrategy);
        $cachingFileResponse->create(__FILE__, "text/plain", []);
    }

    //@TODO - this needs way more tests.
}
