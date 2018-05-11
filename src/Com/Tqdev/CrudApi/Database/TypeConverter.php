<?php
namespace Com\Tqdev\CrudApi\Database;

class TypeConverter
{
    private $driver;

    public function __construct(String $driver)
    {
        $this->driver = $driver;
    }

    private $fromSimplifiedJdbc = [
        'mysql' => [
            'clob' => 'longtext',
            'boolean' => 'bit',
            'blob' => 'longblob',
            'timestamp' => 'datetime',
        ],
    ];

    private $toJdbc = [
        'simplified' => [
            'char' => 'varchar',
            'longvarchar' => 'clob',
            'nchar' => 'varchar',
            'nvarchar' => 'varchar',
            'longnvarchar' => 'clob',
            'binary' => 'varbinary',
            'longvarbinary' => 'blob',
            'tinyint' => 'integer',
            'smallint' => 'integer',
            'real' => 'float',
            'numeric' => 'decimal',
            'time_with_timezone' => 'time',
            'timestamp_with_timezone' => 'timestamp',
        ],
        'mysql' => [
            'tinyint(1)' => 'boolean',
            'bit(0)' => 'boolean',
            'bit(1)' => 'boolean',
            'tinyblob' => 'blob',
            'mediumblob' => 'blob',
            'longblob' => 'blob',
            'tinytext' => 'clob',
            'mediumtext' => 'clob',
            'longtext' => 'clob',
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
            'jsonb' => 'clob',
            'text' => 'clob',
            'double precision' => 'double',
        ],
        // source: https://docs.microsoft.com/en-us/sql/connect/jdbc/using-basic-data-types?view=sql-server-2017
        'mssql' => [
            'datetime' => 'timestamp',
            'datetime2' => 'timestamp',
            'float' => 'double',
            'image' => 'longvarbinary',
            'int' => 'integer',
            'money' => 'decimal',
            'ntext' => 'longnvarchar',
            'smalldatetime' => 'timestamp',
            'smallmoney' => 'decimal',
            'text' => 'longvarchar',
            'timestamp' => 'binary',
            'tinyint' => 'tinyint',
            'udt' => 'varbinary',
            'uniqueidentifier' => 'char',
            'xml' => 'longnvarchar',
        ],
    ];

    // source: https://docs.oracle.com/javase/9/docs/api/java/sql/Types.html
    private $valid = [
        //'array' => true,
        'bigint' => true,
        'binary' => true,
        'bit' => true,
        'blob' => true,
        'boolean' => true,
        'char' => true,
        'clob' => true,
        //'datalink' => true,
        'date' => true,
        'decimal' => true,
        'distinct' => true,
        'double' => true,
        'float' => true,
        'integer' => true,
        //'java_object' => true,
        'longnvarchar' => true,
        'longvarbinary' => true,
        'longvarchar' => true,
        'nchar' => true,
        'nclob' => true,
        //'null' => true,
        'numeric' => true,
        'nvarchar' => true,
        //'other' => true,
        'real' => true,
        //'ref' => true,
        //'ref_cursor' => true,
        //'rowid' => true,
        'smallint' => true,
        //'sqlxml' => true,
        //'struct' => true,
        'time' => true,
        'time_with_timezone' => true,
        'timestamp' => true,
        'timestamp_with_timezone' => true,
        'tinyint' => true,
        'varbinary' => true,
        'varchar' => true,
        // extra:
        'geometry' => true,
    ];

    public function toJdbc(String $type, int $size): String
    {
        $jdbcType = strtolower($type);
        if (isset($this->toJdbc[$this->driver]["$jdbcType($size)"])) {
            $jdbcType = $this->toJdbc[$this->driver]["$jdbcType($size)"];
        }
        if (isset($this->toJdbc[$this->driver][$jdbcType])) {
            $jdbcType = $this->toJdbc[$this->driver][$jdbcType];
        }
        if (isset($this->toJdbc['simplified'][$jdbcType])) {
            $jdbcType = $this->toJdbc['simplified'][$jdbcType];
        }
        if (!isset($this->valid[$jdbcType])) {
            throw new \Exception("Unsupported type '$jdbcType' for driver '$this->driver'");
        }
        return $jdbcType;
    }
}
