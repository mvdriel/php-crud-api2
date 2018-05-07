<?php
namespace Com\Tqdev\CrudApi\Meta\Reflection;

class ReflectedColumn implements \JsonSerializable
{

    private $name;
    private $nullable;
    private $type;
    private $jdbcType;
    private $length;
    private $precision;
    private $scale;
    private $pk;
    private $fk;

    public function __construct(GenericMeta $meta, array $columnResult)
    {
        $typeConverter = $meta->getTypeConverter();
        $this->name = $columnResult['COLUMN_NAME'];
        $this->nullable = in_array(strtoupper($columnResult['IS_NULLABLE']), ['TRUE', 'YES', 'T', 'Y', '1']);
        $this->type = $columnResult['DATA_TYPE'];
        $this->jdbcType = $typeConverter->toJdbc($this->type);
        $this->length = $columnResult['CHARACTER_MAXIMUM_LENGTH'];
        $this->precision = $columnResult['NUMERIC_PRECISION'];
        $this->scale = $columnResult['NUMERIC_SCALE'];
        $this->pk = false;
        $this->fk = '';
    }

    public function getName(): String
    {
        return $this->name;
    }

    public function getNullable(): bool
    {
        return $this->nullable;
    }

    public function getType(): String
    {
        return $this->type;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function getPrecision(): int
    {
        return $this->precision;
    }

    public function getScale(): int
    {
        return $this->scale;
    }

    public function hasLength(): bool
    {
        return in_array($this->jdbcType, ['varchar', 'char', 'longvarchar', 'varbinary', 'binary']);
    }

    public function hasPrecision(): bool
    {
        return $this->jdbcType == 'numeric';
    }

    public function hasScale(): bool
    {
        return $this->jdbcType == 'numeric';
    }

    public function isBinary(): bool
    {
        return in_array($jdbcType, ['blob', 'varbinary', 'binary']);
    }

    public function isBoolean(): bool
    {
        return $this->jdbcType == 'bit';
    }

    public function isGeometry(): bool
    {
        return $this->jdbcType == 'geometry';
    }

    public function setPk($value): void
    {
        $this->pk = $value;
    }

    public function getPk(): bool
    {
        return $this->pk;
    }

    public function setFk($value): void
    {
        $this->fk = $value;
    }

    public function getFk(): String
    {
        return $this->fk;
    }

    public function jsonSerialize()
    {
        $json = array();
        $json['type'] = $this->jdbcType;
        if ($this->pk) {
            $json['pk'] = true;
        }
        if ($this->nullable) {
            $json['nullable'] = true;
        }
        if ($this->hasLength()) {
            $json['length'] = $this->length;
        }
        if ($this->hasPrecision()) {
            $json['precision'] = $this->precision;
        }
        if ($this->hasScale()) {
            $json['scale'] = $this->scale;
        }
        if ($this->fk) {
            $json['fk'] = $this->fk;
        }
        return $json;
    }
}
