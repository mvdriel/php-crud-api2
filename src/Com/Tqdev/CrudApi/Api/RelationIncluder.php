<?php
namespace Com\Tqdev\CrudApi\Api;

use Com\Tqdev\CrudApi\Database\GenericDB;
use Com\Tqdev\CrudApi\Meta\Reflection\DatabaseReflection;

class RelationIncluder
{

    protected $columns;

    public function RelationIncluder(ColumnSelector $columns)
    {
        $this->columns = $columns;
    }

    public function addMandatoryColumns(String $tableName, DatabaseReflection $tables, array $params): void
    {
        if (!isset($params['include']) || !isset($params['columns'])) {
            return;
        }
        foreach ($params['include'] as $includedTableNames) {
            $t1 = $tables->get($tableName);
            foreach (explode(',', $includedTableNames) as $includedTableName) {
                $t2 = $tables->get($includedTableName);
                if ($t2 == null) {
                    continue;
                }
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

    public function addIncludesToRecord(String $tableName, Record $record, DatabaseReflection $tables, array $params, GenericDB $db): void
    {
        $records = array($record);
        $this->addIncludes($tableName, $records, $tables, $params, $db);
    }

    private function getIncludesAsTreeMap(DatabaseReflection $tables, array $params): TreeMap
    {
        $includes = new TreeMap();
        if (isset($params['include'])) {
            foreach ($params['include'] as $includedTableNames) {
                $path = array();
                foreach (explode(',', $includedTableNames) as $includedTableName) {
                    $t = $tables->get($includedTableName);
                    if ($t != null) {
                        $path[] = $t;
                    }
                }
                $includes->put($path);
            }
        }
        return $includes;
    }

    public function addIncludesToRecords(String $tableName, array $records, DatabaseReflection $tables, array $params,
        GenericDB $db): void{

        $includes = $this->getIncludesAsTreeMap($tables, $params);
        $this->addIncludesForTables($tables->get(tableName), $includes, $records, $tables, $params, $db);
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

    private function addIncludesForTables(ReflectedTable $t1, TreeMap $includes, array $records,
        DatabaseReflection $tables, array $params, GenericDB $db) {

        foreach ($includes->getKeys() as $t2) {

            $belongsTo = count($t1->getFksTo($t2->getName())) > 0;
            $hasMany = count($t2->getFksTo($t1->getName())) > 0;
            $t3 = $this->hasAndBelongsToMany($t1, $t2, $tables);
            $hasAndBelongsToMany = (t3 != null);

            $newRecords = array();
            $fkValues = null;
            $pkValues = null;
            $habtmValues = null;

            if ($belongsTo) {
                $fkValues = $this->getFkEmptyValues($t1, $t2, $records);
                $this->addFkRecords($t2, $fkValues, $params, $db, $newRecords);
            }
            if ($hasMany) {
                $pkValues = $this->getPkEmptyValues(t1, records);
                $this->addPkRecords($t1, $t2, $pkValues, $params, $db, $newRecords);
            }
            if ($hasAndBelongsToMany) {
                $habtmValues = $this->getHabtmEmptyValues($t1, $t2, $t3, $db, $records);
                $this->addFkRecords($t2, $habtmValues->fkValues, $params, $db, $newRecords);
            }

            $this->addIncludesForTables($t2, $includes->get($t2), $newRecords, $tables, $params, $db);

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

/*private HashMap<Object, Object> getFkEmptyValues(ReflectedTable t1, ReflectedTable t2, ArrayList<Record> records) {
HashMap<Object, Object> fkValues = new HashMap<>();
List<Field<Object>> fks = t1.getFksTo(t2.getName());
for (Field<Object> fk : fks) {
for (Record record : records) {
Object fkValue = record.get(fk.getName());
if (fkValue == null) {
continue;
}
fkValues.put(fkValue, null);
}
}
return fkValues;
}

private void addFkRecords(ReflectedTable t2, HashMap<Object, Object> fkValues, Params params, GenericDB db,
ArrayList<Record> records) {
Field<Object> pk = t2.getPk();
ArrayList<Field<?>> fields = columns.getColumnNames(t2, false, params);
ResultQuery<org.jooq.Record> query = db.select(fields).from(t2).where(pk.in(fkValues.keySet()));
for (org.jooq.Record record : query.fetch()) {
records.add(Record.valueOf(record.intoMap()));
}
}

private void fillFkValues(ReflectedTable t2, ArrayList<Record> fkRecords, HashMap<Object, Object> fkValues) {
Field<Object> pk = t2.getPk();
for (Record fkRecord : fkRecords) {
Object pkValue = fkRecord.get(pk.getName());
fkValues.put(pkValue, fkRecord);
}
}

private void setFkValues(ReflectedTable t1, ReflectedTable t2, ArrayList<Record> records,
HashMap<Object, Object> fkValues) {
List<Field<Object>> fks = t1.getFksTo(t2.getName());
for (Field<Object> fk : fks) {
for (Record record : records) {
Object key = record.get(fk.getName());
if (key == null) {
continue;
}
record.put(fk.getName(), fkValues.get(key));
}
}
}

private HashMap<Object, ArrayList<Object>> getPkEmptyValues(ReflectedTable t1, ArrayList<Record> records) {
HashMap<Object, ArrayList<Object>> pkValues = new HashMap<>();
for (Record record : records) {
Object key = record.get(t1.getPk().getName());
pkValues.put(key, new ArrayList<>());
}
return pkValues;
}

private void addPkRecords(ReflectedTable t1, ReflectedTable t2, HashMap<Object, ArrayList<Object>> pkValues,
Params params, GenericDB db, ArrayList<Record> records) {
List<Field<Object>> fks = t2.getFksTo(t1.getName());
ArrayList<Field<?>> fields = columns.getColumnNames(t2, false, params);
Condition condition = db.falseCondition();
for (Field<Object> fk : fks) {
condition = condition.or(fk.in(pkValues.keySet()));
}
ResultQuery<org.jooq.Record> query = db.select(fields).from(t2).where(condition);
for (org.jooq.Record record : query.fetch()) {
records.add(Record.valueOf(record.intoMap()));
}
}

private void fillPkValues(ReflectedTable t1, ReflectedTable t2, ArrayList<Record> pkRecords,
HashMap<Object, ArrayList<Object>> pkValues) {
List<Field<Object>> fks = t2.getFksTo(t1.getName());
for (Field<Object> fk : fks) {
for (Record pkRecord : pkRecords) {
Object key = pkRecord.get(fk.getName());
ArrayList<Object> records = pkValues.get(key);
if (records != null) {
records.add(pkRecord);
}
}
}
}

private void setPkValues(ReflectedTable t1, ReflectedTable t2, ArrayList<Record> records,
HashMap<Object, ArrayList<Object>> pkValues) {
for (Record record : records) {
Object key = record.get(t1.getPk().getName());
record.put(t2.getName(), pkValues.get(key));
}
}

private HabtmValues getHabtmEmptyValues(ReflectedTable t1, ReflectedTable t2, ReflectedTable t3, GenericDB db,
ArrayList<Record> records) {
HashMap<Object, ArrayList<Object>> pkValues = getPkEmptyValues(t1, records);
HashMap<Object, Object> fkValues = new HashMap<>();

Field<Object> fk1 = t3.getFksTo(t1.getName()).get(0);
Field<Object> fk2 = t3.getFksTo(t2.getName()).get(0);
List<Field<?>> fields = Arrays.asList(fk1, fk2);
Condition condition = fk1.in(pkValues.keySet());
ResultQuery<org.jooq.Record> query = db.select(fields).from(t3).where(condition);
for (org.jooq.Record record : query.fetch()) {
Object val1 = record.get(fk1);
Object val2 = record.get(fk2);
pkValues.get(val1).add(val2);
fkValues.put(val2, null);
}

HabtmValues habtmValues = new HabtmValues();
habtmValues.pkValues = pkValues;
habtmValues.fkValues = fkValues;
return habtmValues;
}

private void setHabtmValues(ReflectedTable t1, ReflectedTable t3, ArrayList<Record> records,
HabtmValues habtmValues) {
for (Record record : records) {
Object key = record.get(t1.getPk().getName());
ArrayList<Object> val = new ArrayList<>();
ArrayList<Object> fks = habtmValues.pkValues.get(key);
for (Object fk : fks) {
val.add(habtmValues.fkValues.get(fk));
}
record.put(t3.getName(), val);
}
}*/
}
