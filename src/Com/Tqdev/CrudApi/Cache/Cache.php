<?php
namespace Com\Tqdev\CrudApi\Cache;

interface Cache
{
    public function set(String $key, $value, int $ttl = 0): bool;
    public function get(String $key) /*: ?object*/;
    public function clear(): bool;
}
