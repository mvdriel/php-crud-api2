<?php
namespace Com\Tqdev\CrudApi\Database;

class GenericMeta {
    
    protected $pdo;
    protected $driver;
    protected $database;

    public function __construct(\PDO $pdo, String $driver, String $database = null) {
        $this->pdo = $pdo;
        $this->driver = $driver;
        $this->database = $database;
    }

    public function getTables(): array {
        $stmt = $this->pdo->prepare('SELECT "TABLE_NAME" FROM "INFORMATION_SCHEMA"."TABLES" WHERE "TABLE_SCHEMA" = :database');
        $stmt->execute(['database' => $this->database]);
        return $stmt->fetchAll();
    }

    public function getTableColumns(String $tableName): array {
        $stmt = $this->pdo->prepare('SELECT "COLUMN_NAME", "IS_NULLABLE", "DATA_TYPE", "CHARACTER_MAXIMUM_LENGTH", "NUMERIC_PRECISION", "NUMERIC_SCALE" FROM "INFORMATION_SCHEMA"."COLUMNS" WHERE "TABLE_NAME" = :tableName AND "TABLE_SCHEMA" = :database');
        $stmt->execute(['tableName' => $tableName, 'database' => $this->database]);
        return $stmt->fetchAll();
    }
}