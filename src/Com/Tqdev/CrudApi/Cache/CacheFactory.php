<?php
namespace Com\Tqdev\CrudApi\Cache;

use Com\Tqdev\CrudApi\Config;

class CacheFactory
{
    public static function create(Config $config): Cache
    {
        switch ($config->getCacheType()) {
            case 'TempFile':
                $cache = new TempFileCache($config->getCachePath());
                break;
            default:
                $cache = new NoCache();
        }
        return $cache;
    }
}
