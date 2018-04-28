<?php
namespace Com\Tqdev\CrudApi\Api;

class TreeMap
{

    protected $branches = array();

    public function put(array $path)
    {
        if (count($path) == 0) {
            return;
        }
        $key = array_shift($path);
        if (!isset($this->branches[$key])) {
            $this->branches[$key] = new TreeMap();
        }
        $branches = $this->branches[$key];
        $branches->put($path);
    }

    public function getKeys(): array
    {
        return array_keys($this->branches);
    }

    public function get($key): PathTree
    {
        return $this->branches[$key];
    }
}
