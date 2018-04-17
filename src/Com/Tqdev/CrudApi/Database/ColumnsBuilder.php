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

    protected function quoteColumnName(ReflectedColumn $column): String {
        return '"'.$column->getName().'"';
    }

    public function getOrderBy(array $columnOrdering) {
        $results = array();
		foreach ($columnOrdering as $i=>list($columnName, $ordering)) {
            $column = $table->get($columnName);
            $quotedColumnName = $this->quoteColumnName($column);
            $results[] = $quotedColumnName.' '.$ordering;
        }
        return implode(',', $results);
    }

    public function getSelect(ReflectedTable $table, array $columnNames): String {
        $results = array();
		foreach ($columnNames as $columnName) {
			$column = $table->get($columnName);
            $quotedColumnName = $this->quoteColumnName($column);
            $results[] = $quotedColumnName;
		}
		return implode(',', $results);
    }

    public function getInsert(ReflectedTable $table, array $columnValues): String {
        $columns = array();
		$values = array();
		foreach ($columnValues as $columnName => $columnValue) {
			$column = $table->get($columnName);
            $quotedColumnName = $this->quoteColumnName($column);
            $columns[] = $quotedColumnName;
            $values[] = '?';
		}
		return '('.implode(',', $columns).') VALUES ('.implode(',', $values).')';
    }

	public function getUpdate(ReflectedTable $table, array $columnValues): String {
        $results = array();
		foreach ($columnValues as $columnName => $columnValue) {
            $column = $table->get($columnName);
            $quotedColumnName = $this->quoteColumnName($column);
            $results[] = $quotedColumnName.'=?';
		}
		return implode(',', $results);
    }

    public function getIncrement(ReflectedTable $table, array $columnValues): String {
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