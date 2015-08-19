<?php

namespace Tier\Caching;

class CachingStrategyDisabled implements CachingStrategy
{
    public function getHeaders($lastModified)
    {
        return array(
            'Pragma' => 'no-cache',
            'Cache-Control' => 'no-transform, no-cache, no-store',
        );
    }
}
