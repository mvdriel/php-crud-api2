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
        $stmt = $this->pdo->prepare('SELECT "TABLE_NAME" FROM "INFORMATION_SCHEMA"."TABLES" WHERE "TABLE_TYPE" IN (?,?) AND "TABLE_SCHEMA" = ?');
        $stmt->execute(['BASE TABLE', 'VIEW', $this->database]);
        return $stmt->fetchAll();
    }

    public function getTableColumns(String $tableName): array {
        $stmt = $this->pdo->prepare('SELECT "COLUMN_NAME", "IS_NULLABLE", "DATA_TYPE", "CHARACTER_MAXIMUM_LENGTH", "NUMERIC_PRECISION", "NUMERIC_SCALE" FROM "INFORMATION_SCHEMA"."COLUMNS" WHERE "TABLE_NAME" = ? AND "TABLE_SCHEMA" = ?');
        $stmt->execute([$tableName, $this->database]);
        return $stmt->fetchAll();
    }

    public function getTablePrimaryKeys(String $tableName): array {
        $stmt = $this->pdo->prepare('SELECT "COLUMN_NAME" FROM "INFORMATION_SCHEMA"."KEY_COLUMN_USAGE" WHERE "CONSTRAINT_NAME" = ? AND "TABLE_NAME" = ? AND "TABLE_SCHEMA" = ?');
        $stmt->execute(['PRIMARY', $tableName, $this->database]);
        $results = $stmt->fetchAll();
        $primaryKeys = [];
        foreach ($results as $result) {
            $primaryKeys[] = $result['COLUMN_NAME'];
        }
        return $primaryKeys;
    }

    public function getTableForeignKeys(String $tableName): array {
        $stmt = $this->pdo->prepare('SELECT "COLUMN_NAME", "REFERENCED_TABLE_NAME" FROM "INFORMATION_SCHEMA"."KEY_COLUMN_USAGE" WHERE "REFERENCED_TABLE_NAME" IS NOT NULL AND "TABLE_NAME" = ? AND "TABLE_SCHEMA" = ?');
        $stmt->execute([$tableName, $this->database]);
        $results = $stmt->fetchAll();
        $foreignKeys = [];
        foreach ($results as $result) {
            $foreignKeys[$result['COLUMN_NAME']] = $result['REFERENCED_TABLE_NAME'];
        }
        return $foreignKeys;
    }
}