<?php
namespace Com\Tqdev\CrudApi\Meta\Reflection;

use Com\Tqdev\CrudApi\Database\GenericMeta;

class ReflectedColumn implements \JsonSerializable
{
    const DEFAULT_LENGTH = 255;
    const DEFAULT_PRECISION = 19;
    const DEFAULT_SCALE = 4;

    private $name;
    private $nullable;
    private $type;
    private $length;
    private $precision;
    private $scale;
    private $pk;
    private $fk;

    public function __construct(GenericMeta $meta, array $columnResult)
    {
        $this->name = $columnResult['COLUMN_NAME'];
        $this->nullable = in_array(strtoupper($columnResult['IS_NULLABLE']), ['TRUE', 'YES', 'T', 'Y', '1']);
        $this->length = $columnResult['CHARACTER_MAXIMUM_LENGTH'];
        $this->type = $meta->getTypeConverter()->toJdbc($columnResult['DATA_TYPE'], $this->length + 0);
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
        return $this->length ?: DEFAULT_LENGTH;
    }

    public function getPrecision(): int
    {
        return $this->precision ?: DEFAULT_PRECISION;
    }

    public function getScale(): int
    {
        return $this->scale ?: DEFAULT_SCALE;
    }

    public function hasLength(): bool
    {
        return in_array($this->type, ['varchar', 'varbinary']);
    }

    public function hasPrecision(): bool
    {
        return $this->type == 'decimal';
    }

    public function hasScale(): bool
    {
        return $this->type == 'decimal';
    }

    public function isBinary(): bool
    {
        return in_array($this->type, ['blob', 'varbinary']);
    }

    public function isBoolean(): bool
    {
        return $this->type == 'boolean';
    }

    public function isGeometry(): bool
    {
        return $this->type == 'geometry';
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
        $json['name'] = $this->name;
        $json['type'] = $this->type;
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
