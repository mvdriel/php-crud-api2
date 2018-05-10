<?php
namespace Com\Tqdev\CrudApi\Cache;

class TempFileCache implements Cache
{
    private $path;
    private $md5;
    private $segments;

    public function __construct(String $config)
    {
        $this->segments = [];
        $s = PATH_SEPARATOR;
        if ($config == '') {
            $this->path = sys_get_temp_dir();
        } elseif (strpos($config, $s) === false) {
            $this->path = $config;
        } else {
            list($path, $segments) = explode($s, $config);
            $this->path = $path;
            $this->segments = explode(',', $segments);
        }
    }

    private function getFileName(String $key): String
    {
        $s = DIRECTORY_SEPARATOR;
        $md5 = md5($key);
        $filename = rtrim($this->path, $s) . $s;
        $i = 0;
        foreach ($this->segments as $segment) {
            $filename .= substr($md5, $i, $segment) . $s;
            $i += $segment;
        }
        $filename .= $md5 . '.cache';
        return $filename;
    }

    public function set(String $key, $value, int $ttl = 0): bool
    {
        $filename = $this->getFileName($key);
        $dirname = dirname($filename);
        if (!file_exists($dirname)) {
            if (!mkdir($dirname, 0755, true)) {
                return false;
            }
        }
        $string = $ttl . '|' . serialize($value);
        return file_put_contents($filename, $string, LOCK_EX) !== false;
    }

    public function get(String $key, bool $stale = false)
    {
        $filename = $this->getFileName($key);
        if (!file_exists($filename)) {
            return null;
        }
        $data = file_get_contents($filename);
        if ($data === false) {
            return null;
        }
        list($ttl, $string) = explode('|', $data, 2);
        if ($ttl > 0 && time() - filemtime($filename) > $ttl) {
            if ($stale) {
                touch($filename);
            }
            return null;
        }
        return unserialize($string);
    }

    private function _clear(String $path, array $segments): void
    {
        $entries = scandir($path);
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $filename = $path . DIRECTORY_SEPARATOR . $entry;
            if (count($segments) == 0) {
                if (substr($entry, -6) != '.cache') {
                    continue;
                }
                if (is_file($filename)) {
                    unlink($filename);
                }
            } else {
                if (strlen($entry) != $segments[0]) {
                    continue;
                }
                if (is_dir($filename)) {
                    $this->_clear($filename, array_slice($segments, 1));
                    rmdir($filename);
                }
            }
        }
    }

    public function clear(): bool
    {
        if (!file_exists($this->path) || !is_dir($this->path)) {
            return false;
        }
        $this->_clear($this->path, array_filter($this->segments));
        return true;
    }
}
