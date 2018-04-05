<?php
namespace Com\Tqdev\CrudApi\Database;

class GenericDB {
    
    protected $driver;
    protected $database;
    protected $pdo;

    private function getDsn(String $driver, String $address, String $port = null, String $database = null): String {
        switch($driver) {
            case 'mysql':
            $dsn = "$driver:host=$address;port=$port;dbname=$database;charset=utf8mb4";
            break;
        }
        return $dsn;
    }

    public function getCommands(String $driver) {
        switch($driver) {
            case 'mysql':
            return [
                'SET SESSION sql_warnings=1',
                'SET NAMES utf8',
                'SET SESSION sql_mode = "ANSI,TRADITIONAL"',
            ];
        }
        return [];
    }

    public function __construct(String $driver, String $address, String $port = null, String $database = null, String $username = null, String $password = null) {
        $options = array(
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => FALSE,
        );
        $this->driver = $driver;
        $this->database = $database;
        $dsn = $this->getDsn($driver, $address, $port, $database);
        $this->pdo = new \PDO($dsn, $username, $password, $options);
        $commands = $this->getCommands($driver);
        foreach ($commands as $command){
            $this->pdo->query($command);
        }
    }

    public function metaGetTables(): array {
        $stmt = $this->pdo->prepare('SELECT "TABLE_NAME" FROM "INFORMATION_SCHEMA"."TABLES" WHERE "TABLE_SCHEMA" = :database');
        $stmt->execute(['database' => $this->database]);
        return $stmt->fetchAll();
    }

    public function metaGetTableColumns(String $tableName): array {
        $stmt = $this->pdo->prepare('SELECT "COLUMN_NAME", "IS_NULLABLE", "DATA_TYPE", "CHARACTER_MAXIMUM_LENGTH", "NUMERIC_PRECISION", "NUMERIC_SCALE" FROM "INFORMATION_SCHEMA"."COLUMNS" WHERE "TABLE_NAME" = :tableName AND "TABLE_SCHEMA" = :database');
        $stmt->execute(['tableName' => $tableName, 'database' => $this->database]);
        return $stmt->fetchAll();
    }
}