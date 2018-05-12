<?php
namespace Com\Tqdev\CrudApi\Cache;

use Com\Tqdev\CrudApi\Config;

class CacheFactory
{
    const PREFIX = 'phpcrudapi-';

    private static function getPrefix(): String
    {
        $id = substr(md5(__FILE__), 0, 8);
        return self::PREFIX . $id . '-';
    }

    public static function create(Config $config): Cache
    {
        switch ($config->getCacheType()) {
            case 'TempFile':
                $cache = new TempFileCache(self::getPrefix(), $config->getCachePath());
                break;
            case 'Redis':
                $cache = new RedisCache(self::getPrefix(), $config->getCachePath());
                break;
            case 'Memcache':
                $cache = new MemcacheCache(self::getPrefix(), $config->getCachePath());
                break;
            case 'Memcached':
                $cache = new MemcachedCache(self::getPrefix(), $config->getCachePath());
                break;
            default:
                $cache = new NoCache();
        }
        return $cache;
    }
}
