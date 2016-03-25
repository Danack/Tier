<?php

namespace TierTest\ResponseBody;

use TierTest\BaseTestCase;
use Room11\HTTP\RequestHeaders\ArrayRequestHeaders;
use Tier\Body\CallableFileGenerator;
use Tier\Body\CachingGeneratingFileBodyFactory;
use Mockery;
use Room11\HTTP\HeadersSet;

class CachingGeneratingFileResponseFactoryTest extends BaseTestCase
{
    private function checkContentDispositionFilename(HeadersSet $headersSet, $filename)
    {
        $this->assertTrue($headersSet->hasHeaders("Content-Disposition"));
        $headers = $headersSet->getHeaders("Content-Disposition");
        $this->assertInternalType('array', $headers);
        $this->assertContains($filename, $headers[0]);
    }

    public function testEmptyRequestHeaders()
    {
        $mockStrategy = Mockery::mock('Room11\Caching\LastModifiedStrategy');
        $mockStrategy
            ->shouldReceive('getHeaders')
            ->once()
            ->andReturn([]);
        
        $requestHeaders = new ArrayRequestHeaders([]);
        $cachingFileResponse = new CachingGeneratingFileBodyFactory(
            $requestHeaders,
            $mockStrategy
        );
        
        $isCalled = false;
        
        $fileGenerator = function () use (&$isCalled) {
            //mock, doesn't need to generate anything.
            $isCalled = true;
            return __FILE__;
        };
        
        $fileGenerator = new CallableFileGenerator(
            $fileGenerator,
            time() - 10
        );
        
        $filename = "foo.txt";

        $body = $cachingFileResponse->create(
            "text/plain",
            $filename,
            $fileGenerator
        );
        /** @var $body \Room11\HTTP\Body\FileBody */
        $this->assertInstanceOf(
            'Room11\HTTP\Body\FileBody',
            $body
        );
        $this->assertEquals(200, $body->getStatusCode());
        $this->assertTrue($isCalled, "File generator wasn't called");
        $this->checkContentDispositionFilename($body->getHeadersSet(), $filename);
    }

    public function testHeadersSentNotModified()
    {
        $mockStrategy = Mockery::mock('Room11\Caching\LastModifiedStrategy');
        $mockStrategy
            ->shouldReceive('getHeaders')
            ->never();
        
        $lastModifiedTime = time() - 10;
        $headers = [
            'If-Modified-Since' => [gmdate('D, d M Y H:i:s', $lastModifiedTime). ' UTC']
        ];

        $requestHeaders = new ArrayRequestHeaders($headers);
        $cachingFileResponse = new CachingGeneratingFileBodyFactory(
            $requestHeaders,
            $mockStrategy
        );

        $isCalled = false;
        $fileGenerator = function () use (&$isCalled) {
            //mock, doesn't need to generate anything.
            $isCalled = true;
            return __FILE__;
        };

        $fileGenerator = new CallableFileGenerator(
            $fileGenerator,
            time() - 1000
        );
        
        $filename = "foo.txt";

        $body = $cachingFileResponse->create(
            "text/plain",
            $filename,
            $fileGenerator
        );
        /** @var $body \Room11\HTTP\Body\FileBody */
        $this->assertInstanceOf(
            'Room11\HTTP\Body\EmptyBody',
            $body
        );
        $this->assertEquals(304, $body->getStatusCode());
        $this->assertFalse($isCalled, "File generator was called and shouldn't be");
    }

    
    public function testHeadersSentButActuallyModified()
    {
        $mockStrategy = Mockery::mock(new \Room11\Caching\LastModified\Revalidate(1000, 100));
        $mockStrategy
            ->shouldReceive('getHeaders')
            ->once()
            ->passthru();
        
        $lastModifiedTime = time() - 1000;
        $headersSet = [
            'If-Modified-Since' => [gmdate('D, d M Y H:i:s', $lastModifiedTime). ' UTC']
        ];

        $requestHeaders = new ArrayRequestHeaders($headersSet);
        $cachingFileResponse = new CachingGeneratingFileBodyFactory(
            $requestHeaders,
            $mockStrategy
        );

        $isCalled = false;
        $fileGenerator = function () use (&$isCalled) {
            //mock, doesn't need to generate anything.
            $isCalled = true;
            return __FILE__;
        };

        //Data has been modified after the client
        $fileGenerator = new CallableFileGenerator(
            $fileGenerator,
            time() - 10
        );

        $filename = "foo.txt";
        
        $body = $cachingFileResponse->create(
            "text/plain",
            $filename,
            $fileGenerator
        );
        /** @var $body \Room11\HTTP\Body\FileBody */
        $this->assertInstanceOf(
            'Room11\HTTP\Body\FileBody',
            $body
        );
        $this->assertEquals(200, $body->getStatusCode());
        $this->assertTrue($isCalled, "File generator wasn't called and should be");

        $headersSet = $body->getHeadersSet();
        $this->assertTrue($headersSet->hasHeaders("Last-modified"));
        $this->checkContentDispositionFilename($body->getHeadersSet(), $filename);
    }
}
