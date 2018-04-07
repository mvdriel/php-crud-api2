<?php
namespace Com\Tqdev\CrudApi\Database;

use Com\Tqdev\CrudApi\Meta\Reflection\ReflectedTable;

class GenericDB {
    
    protected $driver;
    protected $database;
    protected $pdo;
    protected $meta;

    protected function getDsn(String $driver, String $address, String $port = null, String $database = null): String {
        switch($driver) {
            case 'mysql':
            $dsn = "$driver:host=$address;port=$port;dbname=$database;charset=utf8mb4";
            break;
        }
        return $dsn;
    }

    protected function getCommands(String $driver) {
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
        $this->meta = new GenericMeta($this->pdo, $driver, $database);
    }

    public function meta(): GenericMeta {
        return $this->meta;
    }

    private function getColumnNames(array $columns): array {
        $results = array();
        foreach ($columns as $column) {
            $results[] = $column->getName();
        }
        return $results;
    }

    public function selectSingle(array $columns, ReflectedTable $table, String $id)/*: ?array*/ {
        $columnNames = $this->getColumnNames($columns);
        $tableName = $table->getName();
        $pkName = $table->getPk()->getName(); 
        $stmt = $this->pdo->prepare('SELECT "'.implode('","',$columnNames).'" FROM "'.$tableName.'" WHERE "'.$pkName.'" = ?');
        $stmt->execute([$id]);
        return $stmt->fetch()?:null; 
    }

    public function selectMultiple(array $columns, ReflectedTable $table, array $ids): array {
        if (count($ids)==0) {
            return [];
        }
        $columnNames = $this->getColumnNames($columns);
        $tableName = $table->getName();
        $pkName = $table->getPk()->getName(); 
        $qm = str_repeat('?,',count($ids)-1);
        $stmt = $this->pdo->prepare('SELECT "'.implode('","',$columnNames).'" FROM "'.$tableName.'" WHERE "'.$pkName.'" in ('.$qm.'?)');
        $stmt->execute($ids);
        return $stmt->fetchAll();
    }

    public function selectAll(array $columns, ReflectedTable $table): array {
        $columnNames = $this->getColumnNames($columns);
        $tableName = $table->getName();
        $stmt = $this->pdo->prepare('SELECT "'.implode('","',$columnNames).'" FROM "'.$tableName);
        $stmt->execute([]);
        return $stmt->fetchAll();
    }
}
    