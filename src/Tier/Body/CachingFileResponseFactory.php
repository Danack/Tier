<?php

namespace Tier\Body;

use Room11\Caching\LastModifiedStrategy;
use Room11\HTTP\Body\FileBody;

class CachingFileBodyFactory
{
    private $caching;
    
    public function __construct(LastModifiedStrategy $caching)
    {
        $this->caching = $caching;
    }

    /**
     * Create a FileBody that has cache headers set.
     * @param $fileNameToServe
     * @param $contentType
     * @param array $headers
     * @return FileBody
     */
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
