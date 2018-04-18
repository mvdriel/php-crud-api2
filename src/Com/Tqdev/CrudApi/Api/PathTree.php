<?php
namespace Com\Tqdev\CrudApi\Api;

class PathTree {

	protected $values = array();

    protected $leaves = array();

    public function getValues(): array {
        return $this->values;
    }

    public function put(array $path, $value) {
        if (count($path)==0) {
            $this->values[] = $value;
            return;
        }
        $key = array_shift($path);
        if (!isset($this->leaves[$key])) {
            $this->leaves[$key] = new PathTree();
        }
        $tree = $this->leaves[$key];
        $tree->put($path, $value);
    }

    public function getKeys(): array {
        return array_keys($this->leaves);
    }

    public function get($key): PathTree {
        return $this->leaves[$key];
    }
}
