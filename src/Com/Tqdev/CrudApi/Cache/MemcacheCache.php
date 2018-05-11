<?php
namespace Com\Tqdev\CrudApi\Cache;

class MemcacheCache extends MemcachedCache
{
    protected function create(): object
    {
        return new \Memcache();
    }

    public function set(String $key, $value, int $ttl = 0): bool
    {
        return $this->memcache->set($this->prefix . $key, $value, 0, $ttl);
    }
}
