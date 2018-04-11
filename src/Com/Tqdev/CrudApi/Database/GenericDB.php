<?php
namespace Com\Tqdev\CrudApi\Database;

use Com\Tqdev\CrudApi\Meta\Reflection\ReflectedTable;

class GenericDB {
    
    protected $driver;
    protected $database;
    protected $pdo;
    protected $meta;
    protected $columns;

    protected function getDsn(String $address, String $port = null, String $database = null): String {
        switch($this->driver) {
            case 'mysql':
            return "$this->driver:host=$address;port=$port;dbname=$database;charset=utf8mb4";
        }
        return null;
    }

    protected function getCommands() {
        switch($this->driver) {
            case 'mysql':
            return [
                'SET SESSION sql_warnings=1;',
                'SET NAMES utf8;',
                'SET SESSION sql_mode = "ANSI,TRADITIONAL";',
            ];
        }
        return null;
    }

    public function __construct(String $driver, String $address, String $port = null, String $database = null, String $username = null, String $password = null) {
        $options = array(
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
        );
        $this->driver = $driver;
        $this->database = $database;
        $dsn = $this->getDsn($address, $port, $database);
        $this->pdo = new \PDO($dsn, $username, $password, $options);
        $commands = $this->getCommands();
        foreach ($commands as $command){
            $this->pdo->query($command);
        }
        $this->meta = new GenericMeta($this->pdo, $driver, $database);
        $this->columns = new ColumnsBuilder($this->pdo, $driver, $database);
    }

    public function pdo(): \PDO {
        return $this->pdo;
    }

    public function meta(): GenericMeta {
        return $this->meta;
    }

    public function columns(): ColumnsBuilder {
        return $this->columns;
    }

    protected function getLastInsertIdSql(): String {
        switch($this->driver) {
            case 'mysql':
            return 'LAST_INSERT_ID()';
        }
        return null;
    }
    
    public function createSingle(ReflectedTable $table, array $columnValues) {
        $insertColumns = $this->columns()->insert($table, $columnValues);
        $tableName = $table->getName();
        $stmt = $this->pdo->prepare('INSERT INTO "'.$tableName.'" '.$insertColumns);
        $stmt->execute(array_values($columnValues));
        $stmt = $this->pdo->prepare('SELECT '.$this->getLastInsertIdSql());
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function selectSingle(ReflectedTable $table, array $columnNames, String $id)/*: ?\stdClass*/ {
        $selectColumns = $this->columns()->select($table, $columnNames);
        $tableName = $table->getName();
        $pkName = $table->getPk()->getName(); 
        $stmt = $this->pdo->prepare('SELECT '.$selectColumns.' FROM "'.$tableName.'" WHERE "'.$pkName.'" = ?');
        $stmt->execute([$id]);
        return $stmt->fetch()?:null; 
    }

    public function selectMultiple(ReflectedTable $table, array $columnNames, array $ids): array {
        if (count($ids)==0) {
            return [];
        }
        $selectColumns = $this->columns()->select($table, $columnNames);
        $tableName = $table->getName();
        $pkName = $table->getPk()->getName(); 
        $questionMarks = str_repeat('?,',count($ids)-1);
        $stmt = $this->pdo->prepare('SELECT '.$selectColumns.' FROM "'.$tableName.'" WHERE "'.$pkName.'" in ('.$questionMarks.'?)');
        $stmt->execute($ids);
        return $stmt->fetchAll();
    }

    public function selectAll(ReflectedTable $table, array $columnNames): array {
        $selectColumns = $this->columns()->select($table, $columnNames);
        $tableName = $table->getName();
        $stmt = $this->pdo->prepare('SELECT '.$selectColumns.' FROM "'.$tableName);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function updateSingle(ReflectedTable $table, array $columnValues, String $id) {
        $updateColumns = $this->columns()->update($table, $columnValues);
        $tableName = $table->getName();
        $pkName = $table->getPk()->getName(); 
        $stmt = $this->pdo->prepare('UPDATE "'.$tableName.'" SET '.$updateColumns.' WHERE "'.$pkName.'" = ?');
        $stmt->execute(array_merge(array_values($columnValues),[$id]));
        return $stmt->rowCount();
    }

    public function deleteSingle(ReflectedTable $table, String $id) {
        $tableName = $table->getName();
        $pkName = $table->getPk()->getName();
        $stmt = $this->pdo->prepare('DELETE FROM "'.$tableName.'" WHERE "'.$pkName.'" = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount();
    }
}
    