<?php
namespace Com\Tqdev\CrudApi\Database;

class TypeConverter
{
    private $driver;

    public function __construct(String $driver)
    {
        $this->driver = $driver;
    }

    /*private $fromJdbc = [
    'mysql' => [
    'longvarchar' => 'longtext',
    'clob' => 'longtext',
    ],
    ];*/

    private $toJdbc = [
        'mysql' => [
            'tinyblob' => 'blob',
            'mediumblob' => 'blob',
            'longblob' => 'blob',
            'tinytext' => 'clob',
            'mediumtext' => 'clob',
            'longtext' => 'clob',
            'decimal' => 'numeric',
            'text' => 'clob',
            'int' => 'integer',
            'polygon' => 'geometry',
            'point' => 'geometry',
            'datetime' => 'timestamp',
        ],
        'pgsql' => [
            'character varying' => 'varchar',
            'timestamp without time zone' => 'timestamp',
            'bytea' => 'blob',
            'boolean' => 'bit',
            // not supported yet:
            'jsonb' => 'clob',
        ],
    ];

    private $valid = [
        'varchar' => true,
        'char' => true,
        'longvarchar' => true,
        'bit' => true,
        'numeric' => true,
        'tinyint' => true,
        'smallint' => true,
        'integer' => true,
        'bigint' => true,
        'real' => true,
        'float' => true,
        'double' => true,
        'varbinary' => true,
        'binary' => true,
        'date' => true,
        'time' => true,
        'timestamp' => true,
        'clob' => true,
        'blob' => true,
        // not supported yet:
        // 'array' => true,
        // 'ref' => true,
        // 'struct' => true,
        // extra:
        'geometry' => true,
    ];

    public function toJdbc(String $type): String
    {
        $jdbcType = strtolower($type);
        if (isset($this->toJdbc[$this->driver][$jdbcType])) {
            $jdbcType = $this->toJdbc[$this->driver][$jdbcType];
        }
        if (!isset($this->valid[$jdbcType])) {
            throw new \Exception("Unsupported type '$jdbcType' for driver '$this->driver'");
        }
        return $jdbcType;
    }
}
