<?php
namespace Com\Tqdev\CrudApi\Api;

class PathTree {

    private $values = array();

    private $leaves = array();
    
    public function getValues(): array {
        return $values;
    }

    public function put(array $path, $value) {
        if (count($path)==0) {
            $values[] = $value;
            return;
        }
        $key = array_shift($path);
        if (!isset($leaves[$key])) {
            $leaves[$key] = new PathTree();
        }
        $tree = $leaves[$key];
        $tree->put($path, $value);
    }

    public function getKeys(): array {
        return array_keys($this->leaves);
    }

    public function get($key): PathTree {
        return $this->leaves[$key];
    }
}