<?php

namespace App\Message;

class CacheRefreshMessage
{
    private string $cacheKey;

    public function __construct(string $cacheKey)
    {
        $this->cacheKey = $cacheKey;
    }

    public function getCacheKey(): string
    {
        return $this->cacheKey;
    }
}
