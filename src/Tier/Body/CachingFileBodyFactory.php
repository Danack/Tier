<?php

namespace Tier\Body;

use Room11\Caching\LastModifiedStrategy;
use Room11\HTTP\Body\FileBody;
use Room11\HTTP\RequestHeaders;
use Room11\HTTP\Body\EmptyBody;

/**
 * Class CachingFileBodyFactory
 * Factory class that can generate FileBody's with caching headers set
 * based on a last modified strategy.
 */
class CachingFileBodyFactory
{
    private $caching;
    private $requestHeaders;
    
    public function __construct(RequestHeaders $requestHeaders, LastModifiedStrategy $caching)
    {
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

        if (is_array($header) === true) {
            $header = $header[0];
        }
        
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
     * Create a FileBody that has cache headers set.
     * @param $fileNameToServe
     * @param $downloadFilename
     * @param $contentType
     * @param array $headers
     * @return FileBody
     */
    public function create(
        $fileNameToServe,
        $downloadFilename,
        $contentType,
        $headers = []
    ) {
        
        $lastModifiedTime = @filemtime($fileNameToServe);
        $isNotModified = $this->checkNotModified(
            $lastModifiedTime
        );

        if ($isNotModified === true) {
            return new EmptyBody(304);
        }

        $cachingHeaders = $this->caching->getHeaders($lastModifiedTime);
        $headers = array_merge($headers, $cachingHeaders);

        return new FileBody(
            $fileNameToServe,
            $downloadFilename,
            $contentType,
            $headers
        );
    }
}
