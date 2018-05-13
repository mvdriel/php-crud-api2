<?php
namespace Com\Tqdev\CrudApi\Cache;

class NoCache implements Cache
{
    public function __construct()
    {
    }

    public function set(String $key, String $value, int $ttl = 0): bool
    {
        return true;
    }

    public function get(String $key)
    {
        return null;
    }

    public function clear(): bool
    {
        return true;
    }
}
