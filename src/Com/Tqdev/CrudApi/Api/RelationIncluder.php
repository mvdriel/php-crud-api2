<?php
namespace Com\Tqdev\CrudApi\Api;

use Com\Tqdev\CrudApi\Database\GenericDB;
use Com\Tqdev\CrudApi\Meta\Reflection\DatabaseReflection;
use Com\Tqdev\CrudApi\Meta\Reflection\ReflectedTable;

class RelationIncluder
{

    protected $columns;

    public function __construct(ColumnSelector $columns)
    {
        $this->columns = $columns;
    }

    public function addMandatoryColumns(String $tableName, DatabaseReflection $tables, array &$params): void
    {
        if (!isset($params['include']) || !isset($params['columns'])) {
            return;
        }
        foreach ($params['include'] as $includedTableNames) {
            $t1 = $tables->get($tableName);
            foreach (explode(',', $includedTableNames) as $includedTableName) {
                if (!$tables->exists($includedTableName)) {
                    continue;
                }
                $t2 = $tables->get($includedTableName);
                $params['mandatory'] = array();
                $fks1 = $t1->getFksTo($t2->getName());
                $t3 = $this->hasAndBelongsToMany($t1, $t2, $tables);
                if ($t3 != null || count($fks1) > 0) {
                    $params['mandatory'][] = $t2->getName() . '.' . $t2->getPk()->getName();
                }
                foreach ($fks1 as $fk) {
                    $params['mandatory'][] = $t1->getName() . '.' . $fk->getName();
                }
                $fks2 = $t2->getFksTo($t1->getName());
                if ($t3 != null || count($fks2) > 0) {
                    $params['mandatory'][] = $t1->getName() . '.' . $t1->getPk()->getName();
                }
                foreach ($fks2 as $fk) {
                    $params['mandatory'][] = $t2->getName() . '.' . $fk->getName();
                }
                $t1 = $t2;
            }
        }
    }

    public function addIncludesToRecord(ReflectedTable $table, array &$record, DatabaseReflection $tables, array $params, GenericDB $db): void
    {
        $records = array($record);
        $this->addIncludesToRecords($table, $records, $tables, $params, $db);
    }

    private function getIncludesAsPathTree(DatabaseReflection $tables, array $params): PathTree
    {
        $includes = new PathTree();
        if (isset($params['include'])) {
            foreach ($params['include'] as $includedTableNames) {
                $path = array();
                foreach (explode(',', $includedTableNames) as $includedTableName) {
                    $t = $tables->get($includedTableName);
                    if ($t != null) {
                        $path[] = $t->getName();
                    }
                }
                $includes->put($path, null);
            }
        }
        return $includes;
    }

    public function addIncludesToRecords(ReflectedTable $table, array &$records, DatabaseReflection $tables, array $params,
        GenericDB $db): void{

        $includes = $this->getIncludesAsPathTree($tables, $params);
        $this->addIncludesForTables($table, $includes, $records, $tables, $params, $db);
    }

    private function hasAndBelongsToMany(ReflectedTable $t1, ReflectedTable $t2, DatabaseReflection $tables) /*: ?ReflectedTable*/
    {
        foreach ($tables->getTableNames() as $tableName) {
            $t3 = $tables->get($tableName);
            if (count($t3->getFksTo($t1->getName())) > 0 && count($t3->getFksTo($t2->getName())) > 0) {
                return $t3;
            }
        }
        return null;
    }

    private function addIncludesForTables(ReflectedTable $t1, PathTree $includes, array &$records,
        DatabaseReflection $tables, array $params, GenericDB $db) {

        foreach ($includes->getKeys() as $t2Name) {

            $t2 = $tables->get($t2Name);

            $belongsTo = count($t1->getFksTo($t2->getName())) > 0;
            $hasMany = count($t2->getFksTo($t1->getName())) > 0;
            $t3 = $this->hasAndBelongsToMany($t1, $t2, $tables);
            $hasAndBelongsToMany = ($t3 != null);

            $newRecords = array();
            $fkValues = null;
            $pkValues = null;
            $habtmValues = null;

            if ($belongsTo) {
                $fkValues = $this->getFkEmptyValues($t1, $t2, $records);
                $this->addFkRecords($t2, $fkValues, $params, $db, $newRecords);
            }
            if ($hasMany) {
                $pkValues = $this->getPkEmptyValues($t1, $records);
                $this->addPkRecords($t1, $t2, $pkValues, $params, $db, $newRecords);
            }
            if ($hasAndBelongsToMany) {
                $habtmValues = $this->getHabtmEmptyValues($t1, $t2, $t3, $db, $records);
                $this->addFkRecords($t2, $habtmValues->fkValues, $params, $db, $newRecords);
            }

            $this->addIncludesForTables($t2, $includes->get($t2Name), $newRecords, $tables, $params, $db);

            if ($fkValues != null) {
                $this->fillFkValues($t2, $newRecords, $fkValues);
                $this->setFkValues($t1, $t2, $records, $fkValues);
            }
            if ($pkValues != null) {
                $this->fillPkValues($t1, $t2, $newRecords, $pkValues);
                $this->setPkValues($t1, $t2, $records, $pkValues);
            }
            if ($habtmValues != null) {
                $this->fillFkValues($t2, $newRecords, $habtmValues->fkValues);
                $this->setHabtmValues($t1, $t3, $records, $habtmValues);
            }
        }
    }

    private function getFkEmptyValues(ReflectedTable $t1, ReflectedTable $t2, array $records): array
    {
        $fkValues = array();
        $fks = $t1->getFksTo($t2->getName());
        foreach ($fks as $fk) {
            $fkName = $fk->getName();
            foreach ($records as $record) {
                if (isset($record[$fkName])) {
                    $fkValue = $record[$fkName];
                    $fkValues[$fkValue] = null;
                }
            }
        }
        return $fkValues;
    }

    private function addFkRecords(ReflectedTable $t2, array $fkValues, array $params, GenericDB $db, array &$records): void
    {
        $pk = $t2->getPk();
        $columnNames = $this->columns->getNames($t2, false, $params);
        $fkIds = array_keys($fkValues);

        foreach ($db->selectMultiple($t2, $columnNames, $fkIds) as $record) {
            $records[] = $record;
        }
    }

    private function fillFkValues(ReflectedTable $t2, array $fkRecords, array &$fkValues): void
    {
        $pkName = $t2->getPk()->getName();
        foreach ($fkRecords as $fkRecord) {
            $pkValue = $fkRecord[$pkName];
            $fkValues[$pkValue] = $fkRecord;
        }
    }

    private function setFkValues(ReflectedTable $t1, ReflectedTable $t2, array &$records, array $fkValues): void
    {
        $fks = $t1->getFksTo($t2->getName());
        foreach ($fks as $fk) {
            $fkName = $fk->getName();
            foreach ($records as $i => $record) {
                if (isset($record[$fkName])) {
                    $key = $record[$fkName];
                    $records[$i][$fkName] = $fkValues[$key];
                }
            }
        }
    }

    private function getPkEmptyValues(ReflectedTable $t1, array $records): array
    {
        $pkValues = array();
        $pkName = $t1->getPk()->getName();
        foreach ($records as $record) {
            $key = $record[$pkName];
            $pkValues[$key] = array();
        }
        return $pkValues;
    }

    private function addPkRecords(ReflectedTable $t1, ReflectedTable $t2, array $pkValues, array $params, GenericDB $db, array &$records): void
    {
        $fks = $t2->getFksTo($t1->getName());
        $columnNames = $columns->getColumnNames($t2, false, $params);
        $fkIds = array_keys($pkValues);

        foreach ($db->selectMultiple($t2, $columnNames, $fkIds) as $record) {
            $records[] = $record;
        }
    }

    private function fillPkValues(ReflectedTable $t1, ReflectedTable $t2, array $pkRecords, array &$pkValues): void
    {
        $fks = $t2->getFksTo($t1->getName());
        foreach ($fks as $fk) {
            $fkName = $fk->getName();
            foreach ($pkRecords as $pkRecord) {
                $key = $pkRecord[$fkName];
                if (isset($pkValues[$key])) {
                    $pkValues[$key][] = $pkRecord;
                }
            }
        }
    }

    private function setPkValues(ReflectedTable $t1, ReflectedTable $t2, array &$records, array $pkValues): void
    {
        $pkName = $t1->getPk()->getName();
        $t2Name = $t2->getName();
        foreach ($records as $i => $record) {
            $key = $record[$pkName];
            $records[$i][$t2Name] = $pkValues[$key];
        }
    }

    private function getHabtmEmptyValues(ReflectedTable $t1, ReflectedTable $t2, ReflectedTable $t3, GenericDB $db, array $records): HabtmValues
    {
        $pkValues = $this->getPkEmptyValues($t1, $records);
        $fkValues = array();

        $fk1 = $t3->getFksTo($t1->getName())[0];
        $fk2 = $t3->getFksTo($t2->getName())[0];

        $fk1Name = $fk1->getName();
        $fk2Name = $fk2->getName();

        $columnNames = array($fk1Name, $fk2Name);

        $condition = $fk1Name . ',in,' . array_keys($pkValues);
        $records = $db->selectAllUnordered($t3, $columnNames, array($condition));
        foreach ($records as $record) {
            $val1 = $record[$fk1Name];
            $val2 = $record[$fk2Name];
            $pkValues[$val1][] = $val2;
            $fkValues[$val2] = null;
        }

        return new HabtmValues($pkValues, $fkValues);
    }

    private function setHabtmValues(ReflectedTable $t1, ReflectedTable $t3, array &$records, HabtmValues $habtmValues): void
    {
        $pkName = $t1->getPk()->getName();
        $t3Name = $t3->getName();
        foreach ($records as $i => $record) {
            $key = $record[$pkName];
            $val = array();
            $fks = $habtmValues->pkValues[$key];
            foreach ($fks as $fk) {
                $val[] = $habtmValues->fkValues[$fk];
            }
            $records[$i][$t3Name] = $val;
        }
    }
}
