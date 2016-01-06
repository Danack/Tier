<?php

namespace TierTest\ResponseBody;

use Auryn\Injector;
use TierTest\BaseTestCase;
use Jig\JigConfig;
use Jig\Jig;
use FastRoute\RouteCollector;
use Room11\HTTP\Request\CLIRequest;
use Room11\Caching\LastModifiedStrategy;
use Tier\Body\CachingFileBodyFactory;
use Mockery;

class CachingFileResponseFactoryTest extends BaseTestCase
{   
    public function testBasic()
    {
        $mockStrategy = Mockery::mock('Room11\Caching\LastModifiedStrategy');
        $mockStrategy
            ->shouldReceive('getHeaders')
            ->once()
            ->andReturn([]);
        
        
        $cachingFileResponse = new CachingFileBodyFactory($mockStrategy);
        $cachingFileResponse->create(__FILE__, "text/plain", []);

    }
}
