<?php
namespace Com\Tqdev\CrudApi\Database;

use Com\Tqdev\CrudApi\Meta\Reflection\ReflectedTable;

class GenericDB
{

    protected $driver;
    protected $database;
    protected $pdo;
    protected $meta;
    protected $columns;
    protected $conditions;

    protected function getDsn(String $address, String $port = null, String $database = null): String
    {
        switch ($this->driver) {
            case 'mysql':return "$this->driver:host=$address;port=$port;dbname=$database;charset=utf8mb4";
            case 'pgsql':return "$this->driver:host=$address port=$port dbname=$database options='--client_encoding=UTF8'";
        }
    }

    protected function getCommands(): array
    {
        switch ($this->driver) {
            case 'mysql':return [
                    'SET SESSION sql_warnings=1;',
                    'SET NAMES utf8mb4;',
                    'SET SESSION sql_mode = "ANSI,TRADITIONAL";',
                ];
            case 'pgsql':return [
                    "SET NAMES 'UTF8';",
                ];
        }
    }

    public function __construct(String $driver, String $address, String $port = null, String $database = null, String $username = null, String $password = null)
    {
        $options = array(
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        );
        $this->driver = $driver;
        $this->database = $database;
        $dsn = $this->getDsn($address, $port, $database);
        $this->pdo = new \PDO($dsn, $username, $password, $options);
        $commands = $this->getCommands();
        foreach ($commands as $command) {
            $this->pdo->query($command);
        }
        $this->meta = new GenericMeta($this->pdo, $driver, $database);
        $this->columns = new ColumnsBuilder($driver);
        $this->conditions = new ConditionsBuilder($driver);
    }

    public function pdo(): \PDO
    {
        return $this->pdo;
    }

    public function meta(): GenericMeta
    {
        return $this->meta;
    }

    public function createSingle(ReflectedTable $table, array $columnValues)
    {
        $insertColumns = $this->columns->getInsert($table, $columnValues);
        $tableName = $table->getName();
        $stmt = $this->pdo->prepare('INSERT INTO "' . $tableName . '" ' . $insertColumns);
        $stmt->execute(array_values($columnValues));
        $stmt = $this->pdo->prepare('SELECT ' . $this->columns->getLastInsertId());
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function selectSingle(ReflectedTable $table, array $columnNames, String $id) /*: ?\stdClass*/
    {
        $selectColumns = $this->columns->getSelect($table, $columnNames);
        $tableName = $table->getName();
        $pkName = $table->getPk()->getName();
        $stmt = $this->pdo->prepare('SELECT ' . $selectColumns . ' FROM "' . $tableName . '" WHERE "' . $pkName . '" = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function selectMultiple(ReflectedTable $table, array $columnNames, array $ids): array
    {
        if (count($ids) == 0) {
            return [];
        }
        $selectColumns = $this->columns->getSelect($table, $columnNames);
        $tableName = $table->getName();
        $pkName = $table->getPk()->getName();
        $questionMarks = str_repeat('?,', count($ids) - 1);
        $stmt = $this->pdo->prepare('SELECT ' . $selectColumns . ' FROM "' . $tableName . '" WHERE "' . $pkName . '" in (' . $questionMarks . '?)');
        $stmt->execute($ids);
        return $stmt->fetchAll();
    }

    public function selectCount(ReflectedTable $table, array $conditions): int
    {
        $tableName = $table->getName();
        $parameters = array();
        $whereClause = $this->conditions->getWhereClause($conditions, $parameters);
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM "' . $tableName . '"' . $whereClause);
        $stmt->execute($parameters);
        return $stmt->fetchColumn(0);
    }

    public function selectAllUnordered(ReflectedTable $table, array $columnNames, array $conditions): array
    {
        $selectColumns = $this->columns->getSelect($table, $columnNames);
        $tableName = $table->getName();
        $parameters = array();
        $whereClause = $this->conditions->getWhereClause($conditions, $parameters);
        $stmt = $this->pdo->prepare('SELECT ' . $selectColumns . ' FROM "' . $tableName . '"' . $whereClause);
        $stmt->execute($parameters);
        return $stmt->fetchAll();
    }

    public function selectAll(ReflectedTable $table, array $columnNames, array $conditions, array $columnOrdering, int $offset, int $limit): array
    {
        $selectColumns = $this->columns->getSelect($table, $columnNames);
        $tableName = $table->getName();
        $parameters = array();
        $whereClause = $this->conditions->getWhereClause($conditions, $parameters);
        $orderBy = $this->columns->getOrderBy($table, $columnOrdering);
        $offsetLimit = $this->columns->getOffsetLimit($offset, $limit);
        $stmt = $this->pdo->prepare('SELECT ' . $selectColumns . ' FROM "' . $tableName . '"' . $whereClause . ' ORDER BY ' . $orderBy . ' ' . $offsetLimit);
        $stmt->execute($parameters);
        return $stmt->fetchAll();
    }

    public function updateSingle(ReflectedTable $table, array $columnValues, String $id)
    {
        $updateColumns = $this->columns->getUpdate($table, $columnValues);
        $tableName = $table->getName();
        $pkName = $table->getPk()->getName();
        $stmt = $this->pdo->prepare('UPDATE "' . $tableName . '" SET ' . $updateColumns . ' WHERE "' . $pkName . '" = ?');
        $stmt->execute(array_merge(array_values($columnValues), [$id]));
        return $stmt->rowCount();
    }

    public function deleteSingle(ReflectedTable $table, String $id)
    {
        $tableName = $table->getName();
        $pkName = $table->getPk()->getName();
        $stmt = $this->pdo->prepare('DELETE FROM "' . $tableName . '" WHERE "' . $pkName . '" = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount();
    }
}
