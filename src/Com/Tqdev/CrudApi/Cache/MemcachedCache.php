<?php
namespace Com\Tqdev\CrudApi\Cache;

class MemcachedCache implements Cache
{
    protected $prefix;
    protected $memcache;

    public function __construct(String $prefix, String $config)
    {
        $this->prefix = $prefix;
        if ($config == '') {
            $address = 'localhost';
            $port = 11211;
        } elseif (strpos($config, ':') === false) {
            $address = $config;
            $port = 11211;
        } else {
            list($address, $port) = explode(':', $config);
        }
        $this->memcache = $this->create();
        $this->memcache->addServer($address, $port);
    }

    protected function create(): object
    {
        return new \Memcached();
    }

    public function set(String $key, String $value, int $ttl = 0): bool
    {
        return $this->memcache->set($this->prefix . $key, $value, $ttl);
    }

    public function get(String $key)
    {
        return $this->memcache->get($this->prefix . $key) ?: null;
    }

    public function clear(): bool
    {
        return $this->memcache->flush();
    }
}
