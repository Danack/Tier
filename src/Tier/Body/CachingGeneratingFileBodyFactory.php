<?php

namespace Tier\Body;

use Room11\Caching\LastModifiedStrategy;
use Room11\HTTP\Body\FileBody;
use Room11\HTTP\RequestHeaders;
use Room11\HTTP\Body\EmptyBody;
use Tier\Body\FileGenerator;

/**
 * Class CachingGeneratingFileResponseFactory
 * Generates a HTTP\Body that as appropriate either i) a 304 ReponseNotModified or ii) A
 * FileBody with the appropriate caching headers set. If the 304 response is generated,
 * the file generation function is not called. The use case this is used for is to avoid
 * constantly checking if a generated file has already been generated.
 * @package Tier\ResponseBody
 */
class CachingGeneratingFileBodyFactory
{
    private $caching;
    
    /** @var RequestHeaders  */
    private $requestHeaders;
    
    public function __construct(
        RequestHeaders $requestHeaders,
        LastModifiedStrategy $caching
    ) {
        $this->caching = $caching;
        $this->requestHeaders = $requestHeaders;
    }

    /**
     * Returns true if the content held by the client is not modified,
     * according to the If-Modified-Since header, and the contents last
     * modified time.
     * Mising headers, or missing last modified time result in false.
     * @param $lastModifiedTime
     * @return bool
     */
    private function checkNotModified($lastModifiedTime)
    {
        if ($lastModifiedTime === false) {
            return false;
        }
        
        if ($this->requestHeaders->hasHeader('If-Modified-Since') === false) {
            return false;
        }
    
        $header = $this->requestHeaders->getHeader('If-Modified-Since');
        $clientModifiedTime = @strtotime($header);
        
        if ($clientModifiedTime === false) {
            //Failed to parse time string.
            return false;
        }
    
        if ($clientModifiedTime < $lastModifiedTime) {
            return false;
        }
     
        return true;
    }

    /**
     * @param $contentType
     * @param callable|FileGenerator $fileGenerator The callable that generates the file.
     * @param array $headers
     * @return \Room11\HTTP\Body
     */
    public function create(
        $contentType,
        FileGenerator $fileGenerator,
        $headers = []
    ) {
        $lastModifiedTime = $fileGenerator->getModifiedTime();
        
        $isNotModified = $this->checkNotModified(
            $lastModifiedTime
        );

        if ($isNotModified === true) {
            return new EmptyBody(304);
        }

        $filename = $fileGenerator->generate();
        $cachingHeaders = $this->caching->getHeaders($lastModifiedTime);
        $headers = array_merge($headers, $cachingHeaders);

        return new FileBody(
            $filename,
            $contentType,
            $headers
        );
    }
}
