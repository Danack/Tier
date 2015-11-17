<?php

namespace Tier\ResponseBody;

use Room11\Caching\LastModifiedStrategy;
use Room11\HTTP\Body\FileBody;

class CachingFileResponseFactory
{
    private $caching;
    
    public function __construct(LastModifiedStrategy $caching)
    {
        $this->caching = $caching;
    }

    public function create(
        $fileNameToServe,
        $contentType,
        $headers = []
    ) {
        $cachingHeaders = $this->caching->getHeaders(filemtime($fileNameToServe));
        $headers = array_merge($headers, $cachingHeaders);

        return new FileBody(
            $fileNameToServe,
            $contentType,
            $headers
        );
    }
}
