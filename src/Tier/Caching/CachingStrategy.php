<?php

namespace Tier\Caching;

interface Strategy
{
    const CACHING_DISABLED = 'caching.disabled';
    const CACHING_REVALIDATE = 'caching.revalidate';
    const CACHING_TIME = 'caching.time';

    public function getHeaders($lastModified);
}
