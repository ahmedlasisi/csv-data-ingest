<?php
namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Contracts\Cache\ItemInterface;

namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Contracts\Cache\ItemInterface;

class CacheHelper
{
    private CacheInterface $cache;
    private bool $useCache;
    private int $defaultTtl;

    public function __construct(CacheInterface $cache, bool $useCache = true, int $defaultTtl = 3600)
    {
        $this->cache = $cache;
        $this->useCache = $useCache;
        $this->defaultTtl = $defaultTtl;
    }

    /**
     * Fetch from cache or execute a callable if cache is disabled or expired.
     */
    public function get(string $key, callable $callback, int $ttl = null)
    {
        if (!$this->useCache) {
            return $callback();
        }

        $ttl = $ttl ?? $this->defaultTtl;

        return $this->cache->get($key, function (ItemInterface $item) use ($callback, $ttl) {
            $item->expiresAfter($ttl);
            return $callback();
        });
    }

    /**
     * Store data in cache with optional expiration time.
     */
    public function set(string $key, mixed $value, int $ttl = null): void
    {
        if (!$this->useCache) {
            return;
        }

        $ttl = $ttl ?? $this->defaultTtl;

        $this->cache->get($key, function (ItemInterface $item) use ($value, $ttl) {
            $item->expiresAfter($ttl);
            return $value;
        });
    }

    /**
     * Invalidate a specific cache key.
     */
    public function invalidate(string $key): void
    {
        $this->cache->delete($key);
    }

    /**
     * Clear all cache.
     */
    public function clearAllCache(): void
    {
        if ($this->cache instanceof RedisAdapter) {
            $this->cache->clear();
        }
    }
}
