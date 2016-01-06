<?php

namespace Tier\Path;

use Tier\TierException;

class Path
{
    private $path;

    public function __construct($path)
    {
        if ($path === null) {
            throw new TierException(
                "Path cannot be null for class ".get_class($this)
            );
        }
        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }
}
