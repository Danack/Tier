<?php

namespace Tier\Body;

use Room11\Caching\LastModifiedStrategy;
use Room11\HTTP\Body\FileBody;
use Psr\Http\Message\ServerRequestInterface as Request;
use Room11\HTTP\Body\EmptyBody;

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
    
    /** @var Request  */
    private $request;
    
    public function __construct(
        Request $request,
        LastModifiedStrategy $caching
    ) {
        $this->caching = $caching;
        $this->request = $request;
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
        
        if ($this->request->hasHeader('If-Modified-Since') === false) {
            return false;
        }
    
        $header = $this->request->getHeader('If-Modified-Since');
        $clientModifiedTime = @strtotime($header);
        
        if ($clientModifiedTime === false) {
            return false;
        }
    
        if ($clientModifiedTime < $lastModifiedTime) {
            return false;
        }
     
        return true;
    }

    /**
     * @param $fileNameToServe
     * @param $contentType
     * @param callable $fileGenerator The callable that generates the file.
     * @param array $headers
     * @return \Room11\HTTP\Body
     */
    public function create(
        $fileNameToServe,
        $contentType,
        callable $fileGenerator,
        $headers = []
    ) {
        
        $isNotModified = $this->checkNotModified(
            @filemtime($fileNameToServe)
        );

        if ($isNotModified === true) {
            return new EmptyBody(304);
        }

        $fileGenerator();

        $cachingHeaders = $this->caching->getHeaders(filemtime($fileNameToServe));
        $headers = array_merge($headers, $cachingHeaders);

        return new FileBody(
            $fileNameToServe,
            $contentType,
            $headers
        );
    }
}
