<?php
namespace Com\Tqdev\CrudApi\Api;

class PathTree {

	protected $values = array();

    protected $leaves = array();

    public function getValues(): array {
        return $values;
    }

    public function put(array $path, object $value) {
        if (path.isEmpty()) {
            values.add(value);
            return;
        }
        P key = path.removeFirst();
        PathTree<P, T> val = leaves.get(key);
        if (val == null) {
            val = new PathTree<>();
            leaves.put(key, val);
        }
        val.put(path, value);
    }

    public function keySet(): array {
        return array_keys($leaves);
    }

    public function get(P p): PathTree {
        return leaves.get(p);
    }

}
