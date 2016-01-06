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
class CachingGeneratingFileResponseFactory
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

    private function checkIfModified($lastModifiedTime)
    {
        if ($lastModifiedTime === false) {
            return false;
        }
        
        if (!$this->request->hasHeader('If-Modified-Since')) {
            return false;
        }
    
        $header = $this->request->getHeader('If-Modified-Since');
        $clientModifiedTime = @strtotime($header);
        
        if ($clientModifiedTime == false) {
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
        
        $notModifiedHeader = $this->checkIfModified(
            @filemtime($fileNameToServe)
        );

        if ($notModifiedHeader) {
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
