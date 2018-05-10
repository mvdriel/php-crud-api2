<?php
namespace Com\Tqdev\CrudApi\Cache;

class MemcachedCache extends MemcacheCache
{
    protected function create(): object
    {
        return new \Memcached();
    }

    public function set(String $key, $value, int $ttl = 0): bool
    {
        return $this->memcache->set($this->prefix . $key, $value, $ttl);
    }
}
