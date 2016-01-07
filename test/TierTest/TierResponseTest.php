<?php

namespace TierTest;

use Room11\HTTP\Body\EmptyBody;
use Room11\HTTP\Body\TextBody;
use Room11\HTTP\HeadersSet;
use Room11\HTTP\Request\CLIRequest;
use Tier\TierResponse;
use Zend\Diactoros\Stream;

class TierResponseTest extends BaseTestCase
{

    public function testBasic()
    {
        $request = new CLIRequest("/", "example.com");
        $headersSet = new HeadersSet();
        $body = new EmptyBody(200);

        $time = time();
        $response = new TierResponse($request, $headersSet, $body);
        
        $this->assertEquals(
            $request->getProtocolVersion(),
            $response->getProtocolVersion()
        );
        
        $headers = $response->getHeaders();
        $this->assertArrayHasKey("Date", $headers);
        
        $timeFromHeader = strtotime($headers["Date"][0]);
        $this->assertGreaterThanOrEqual($time, $timeFromHeader);
        
        $this->assertEquals(
            $body->getStatusCode(),
            $response->getStatusCode()
        );
        
        //Coverage testing
        $this->assertTrue($response->hasHeader("Date"));
        $dateHeaders = $response->getHeader("Date");
        $timeFromHeader = strtotime($dateHeaders[0]);
        $this->assertGreaterThanOrEqual($time, $timeFromHeader);
        
        $timeAsLine = $response->getHeaderLine("Date");
        $this->assertGreaterThanOrEqual($time, strtotime($timeAsLine));
        
        $this->assertEquals(
            "",
            $response->getHeaderLine("Foo-Bar")
        );
    }

    
    public function testStream()
    {
        $request = new CLIRequest("/", "example.com");
        $headersSet = new HeadersSet();
        $body = new TextBody("This is some text.");
        $response = new TierResponse($request, $headersSet, $body);

        $stream = $response->getBody();
        //How to test this?
    }
    
    
    public function testReasonPhraseCoverage1()
    {
        $reasonPhrase = 'Hey look, an eagle!';
        $request = new CLIRequest("/", "example.com");
        $headersSet = new HeadersSet();
        $body = new EmptyBody(200, $reasonPhrase);

        $response = new TierResponse($request, $headersSet, $body);

        $this->assertEquals(
            $body->getReasonPhrase(),
            $response->getReasonPhrase()
        );
    }
    
    public function testReasonPhraseCoverage2()
    {
        $request = new CLIRequest("/", "example.com");
        $headersSet = new HeadersSet();
        $body = new EmptyBody(420);

        $response = new TierResponse($request, $headersSet, $body);

        $this->assertEquals(
            TierResponse::$phrases[420],
            $response->getReasonPhrase()
        );
    }
 
    public function testReasonPhraseCoverage3()
    {
        $request = new CLIRequest("/", "example.com");
        $headersSet = new HeadersSet();
        $body = new EmptyBody(720);
        $response = new TierResponse($request, $headersSet, $body);
        $this->assertEquals("", $response->getReasonPhrase());
    }
 
    public function testMutationMethods()
    {
        $request = new CLIRequest("/", "example.com");
        $headersSet = new HeadersSet();
        $body = new EmptyBody(420);
        
        $response = new TierResponse($request, $headersSet, $body);
        $response = $response->withProtocolVersion("1.3");
        $statusResponse = $response->withStatus(503);
        
        $this->assertEquals("1.3", $statusResponse->getProtocolVersion());
        $this->assertEquals(503, $statusResponse->getStatusCode());
        
        $statusResponse = $response->withStatus(504, "Immutability is preferable");
        $this->assertEquals(
            "Immutability is preferable",
            $statusResponse->getReasonPhrase()
        );

        $statusResponse = $response->withAddedHeader("Shamoan", "EeeHee");
        $this->assertEquals(
            "EeeHee",
            $statusResponse->getHeaderLine("Shamoan")
        );

        $statusResponse = $response->withoutHeader("Shamoan");
        $this->assertEquals(
            "",
            $statusResponse->getHeaderLine("Shamoan")
        );
       
        $statusResponse = $response->withAddedHeader("Shamoan", ["value1", "value2"]);
        $this->assertEquals(
            "value1,value2",
            $statusResponse->getHeaderLine("Shamoan")
        );

        $singleResponse = $response->withHeader("Shamoan", "single_value");
        $this->assertEquals(
            "single_value",
            $singleResponse->getHeaderLine("Shamoan")
        );
        
        $singleResponse = $singleResponse->withHeader("Shamoan", "updated_single_value");
        $this->assertEquals(
            "updated_single_value",
            $singleResponse->getHeaderLine("Shamoan")
        );
        
        $singleResponse = $response->withHeader("Shamoan", "single_value");
        $multipleResponse = $singleResponse->withHeader("Shamoan", ["updated_value_1", "updated_value_2"]);
        $this->assertEquals(
            "updated_value_1,updated_value_2",
            $multipleResponse->getHeaderLine("Shamoan")
        );

        $emptyResponse = $multipleResponse->withoutHeader("Shamoan");
        $this->assertEquals(
            "",
            $emptyResponse->getHeaderLine("Shamoan")
        );

        $statusResponse = $response->withAddedHeader("Shamoan", ["value1", "value2"]);
        $this->assertEquals(
            "value1,value2",
            $statusResponse->getHeaderLine("Shamoan")
        );

        $body = new Stream('php://temp', 'wb+');
        $body->write("Hello world");
        $body->rewind();
        $bodyResponse = $response->withBody($body);
        $this->assertEquals(
            "Hello world",
            $bodyResponse->getBody()->getContents()
        );
    }
}
