<?php
namespace Com\Tqdev\CrudApi\Database;

class GenericMeta
{

    private $pdo;
    private $driver;
    private $database;

    public function __construct(\PDO $pdo, String $driver, String $database = null)
    {
        $this->pdo = $pdo;
        $this->driver = $driver;
        $this->database = $database;
    }

    private function getTablesSQL(): String
    {
        switch ($this->driver) {
            case 'mysql':return 'SELECT "TABLE_NAME" FROM "INFORMATION_SCHEMA"."TABLES" WHERE "TABLE_TYPE" IN (\'BASE TABLE\', \'VIEW\') AND "TABLE_SCHEMA" = ?';
            case 'pgsql':return 'SELECT "table_name" as "TABLE_NAME" FROM "information_schema"."tables" WHERE "table_type" IN (\'BASE TABLE\', \'VIEW\') AND "table_catalog" = ?';
        }
    }

    private function getTableColumnsSQL(): String
    {
        switch ($this->driver) {
            case 'mysql':return 'SELECT "COLUMN_NAME", "IS_NULLABLE", "DATA_TYPE", "CHARACTER_MAXIMUM_LENGTH", "NUMERIC_PRECISION", "NUMERIC_SCALE" FROM "INFORMATION_SCHEMA"."COLUMNS" WHERE "TABLE_NAME" = ? AND "TABLE_SCHEMA" = ?';
            case 'pgsql':return 'SELECT a.attname AS "COLUMN_NAME", NOT a.attnotnull as "IS_NULLABLE", pg_catalog.format_type(a.atttypid, -1) as "DATA_TYPE", 0 as "CHARACTER_MAXIMUM_LENGTH", 0 as "NUMERIC_PRECISION", 0 as "NUMERIC_SCALE" FROM pg_attribute a JOIN pg_class pgc ON pgc.oid = a.attrelid WHERE pgc.relname = ? AND \'\' <> ? AND a.attnum > 0 AND NOT a.attisdropped;';
        }
    }

    private function getTablePrimaryKeysSQL(): String
    {
        switch ($this->driver) {
            case 'mysql':return 'SELECT "COLUMN_NAME" FROM "INFORMATION_SCHEMA"."KEY_COLUMN_USAGE" WHERE "CONSTRAINT_NAME" = \'PRIMARY\' AND "TABLE_NAME" = ? AND "TABLE_SCHEMA" = ?';
            case 'pgsql':return 'SELECT a.attname AS "COLUMN_NAME" FROM pg_attribute a JOIN pg_constraint c ON (c.conrelid, c.conkey[1]) = (a.attrelid, a.attnum) JOIN pg_class pgc ON pgc.oid = a.attrelid WHERE pgc.relname = ? AND \'\' <> ? AND c.contype = \'p\'';
        }
    }

    private function getTableForeignKeysSQL(): String
    {
        switch ($this->driver) {
            case 'mysql':return 'SELECT "COLUMN_NAME", "REFERENCED_TABLE_NAME" FROM "INFORMATION_SCHEMA"."KEY_COLUMN_USAGE" WHERE "REFERENCED_TABLE_NAME" IS NOT NULL AND "TABLE_NAME" = ? AND "TABLE_SCHEMA" = ?';
            case 'pgsql':return 'SELECT a.attname AS "COLUMN_NAME", c.confrelid::regclass::text AS "REFERENCED_TABLE_NAME" FROM pg_attribute a JOIN pg_constraint c ON (c.conrelid, c.conkey[1]) = (a.attrelid, a.attnum) JOIN pg_class pgc ON pgc.oid = a.attrelid WHERE pgc.relname = ? AND \'\' <> ? AND c.contype  = \'f\'';
        }
    }

    public function getTables(): array
    {
        $stmt = $this->pdo->prepare($this->getTablesSQL());
        $stmt->execute([$this->database]);
        return $stmt->fetchAll();
    }

    public function getTableColumns(String $tableName): array
    {
        $stmt = $this->pdo->prepare($this->getTableColumnsSQL());
        $stmt->execute([$tableName, $this->database]);
        return $stmt->fetchAll();
    }

    public function getTablePrimaryKeys(String $tableName): array
    {
        $stmt = $this->pdo->prepare($this->getTablePrimaryKeysSQL());
        $stmt->execute([$tableName, $this->database]);
        $results = $stmt->fetchAll();
        $primaryKeys = [];
        foreach ($results as $result) {
            $primaryKeys[] = $result['COLUMN_NAME'];
        }
        return $primaryKeys;
    }

    public function getTableForeignKeys(String $tableName): array
    {
        $stmt = $this->pdo->prepare($this->getTableForeignKeysSQL());
        $stmt->execute([$tableName, $this->database]);
        $results = $stmt->fetchAll();
        $foreignKeys = [];
        foreach ($results as $result) {
            $foreignKeys[$result['COLUMN_NAME']] = $result['REFERENCED_TABLE_NAME'];
        }
        return $foreignKeys;
    }
}
