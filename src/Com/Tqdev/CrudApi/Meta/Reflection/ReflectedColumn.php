<?php
namespace Com\Tqdev\CrudApi\Meta\Reflection;

class ReflectedColumn implements \JsonSerializable
{

    private $name;
    private $nullable;
    private $type;
    private $length;
    private $precision;
    private $scale;
    private $value;
    private $pk;
    private $fk;

    public function __construct(array $columnResult)
    {
        $this->name = $columnResult['COLUMN_NAME'];
        $this->nullable = in_array(strtoupper($columnResult['IS_NULLABLE']), ['TRUE', 'YES', 'T', 'Y', '1']);
        $this->type = $columnResult['DATA_TYPE'];
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
        $json['type'] = $this->type;
        if ($this->pk) {
            $json['pk'] = true;
        }
        if ($this->nullable) {
            $json['nullable'] = true;
        }
        if (strpos(strtolower($this->type), 'var') !== false) {
            $json['length'] = $this->length;
        } else if ($this->scale > 0) {
            $json['precision'] = $this->precision;
            $json['scale'] = $this->scale;
        }
        if ($this->fk) {
            $json['fk'] = $this->fk;
        }
        return $json;
    }
}
