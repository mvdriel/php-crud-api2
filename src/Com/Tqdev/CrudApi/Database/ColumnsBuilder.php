<?php
namespace Com\Tqdev\CrudApi\Database;

use Com\Tqdev\CrudApi\Meta\Reflection\ReflectedColumn;
use Com\Tqdev\CrudApi\Meta\Reflection\ReflectedTable;

class ColumnsBuilder {
    
    protected $pdo;
    protected $driver;
    protected $database;

    public function __construct(\PDO $pdo, String $driver, String $database = null) {
        $this->pdo = $pdo;
        $this->driver = $driver;
        $this->database = $database;
    }

    private function quoteColumnName(ReflectedColumn $column): String {
        $name = preg_replace('/[^A-Za-z0-9_-]/','',$column->getName());
        return '"'.$name.'"';
    }

    public function select(ReflectedTable $table, array $columnNames): String {
        $results = array();
		foreach ($columnNames as $columnName) {
			$column = $table->get($columnName);
            $quotedColumnName = $this->quoteColumnName($column);
            $results[] = $quotedColumnName;
		}
		return implode(',', $results);
    }

	public function update(ReflectedTable $table, array $columnValues): String {
        $results = array();
		foreach ($columnValues as $columnName => $columnValue) {
            $column = $table->get($columnName);
            $quotedColumnName = $this->quoteColumnName($column);
            $results[] = $quotedColumnName.'=?';
		}
		return implode(',', $results);
    }

    public function increment(ReflectedTable $table, array $columnValues): String {
        $results = array();
		foreach ($columnValues as $columnName => $columnValue) {
            if (!is_numeric($columnValue)) {
                continue;
            }
            $column = $table->get($columnName);
            $quotedColumnName = $this->quoteColumnName($column);
            $results[] = $quotedColumnName.'='.$quotedColumnName.'+?';
		}
		return implode(',', $results);
	}

}