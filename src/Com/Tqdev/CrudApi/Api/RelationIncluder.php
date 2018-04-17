<?php
namespace Com\Tqdev\CrudApi\Api;

class RelationIncluder {
    
    protected $columns;

    public function RelationIncluder(ColumnSelector $columns) {
        $this->columns = $columns;
    }

}