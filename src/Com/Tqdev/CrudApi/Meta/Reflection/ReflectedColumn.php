<?php
namespace Com\Tqdev\CrudApi\Meta\Reflection;

use Com\Tqdev\CrudApi\Database\GenericMeta;

class ReflectedColumn implements \JsonSerializable
{
    const DEFAULT_LENGTH = 255;
    const DEFAULT_PRECISION = 19;
    const DEFAULT_SCALE = 4;

    private $name;
    private $type;
    private $length;
    private $precision;
    private $scale;
    private $nullable;
    private $pk;
    private $fk;

    public function __construct(String $name, String $type, int $length, int $precision, int $scale, bool $nullable, bool $pk, String $fk)
    {
        $this->name = $name;
        $this->type = $type;
        $this->length = $length;
        $this->precision = $precision;
        $this->scale = $scale;
        $this->nullable = $nullable;
        $this->pk = $pk;
        $this->fk = $fk;
        $this->sanitize();
    }

    public static function fromMeta(GenericMeta $meta, array $columnResult): ReflectedColumn
    {
        $name = $columnResult['COLUMN_NAME'];
        $length = $columnResult['CHARACTER_MAXIMUM_LENGTH'] + 0;
        $type = $meta->getTypeConverter()->toJdbc($columnResult['DATA_TYPE'], $length);
        $precision = $columnResult['NUMERIC_PRECISION'] + 0;
        $scale = $columnResult['NUMERIC_SCALE'] + 0;
        $nullable = in_array(strtoupper($columnResult['IS_NULLABLE']), ['TRUE', 'YES', 'T', 'Y', '1']);
        $pk = false;
        $fk = '';
        return new ReflectedColumn($name, $type, $length, $precision, $scale, $nullable, $pk, $fk);
    }

    public static function fromJson(object $json): ReflectedColumn
    {
        $name = $json->name;
        $type = $json->type;
        $length = isset($json->length) ? $json->length : 0;
        $precision = isset($json->precision) ? $json->precision : 0;
        $scale = isset($json->scale) ? $json->scale : 0;
        $nullable = isset($json->nullable) ? $json->nullable : false;
        $pk = isset($json->pk) ? $json->pk : false;
        $fk = isset($json->fk) ? $json->fk : '';
        return new ReflectedColumn($name, $type, $length, $precision, $scale, $nullable, $pk, $fk);
    }

    private function sanitize()
    {
        $this->length = $this->hasLength() ? $this->getLength() : 0;
        $this->precision = $this->hasPrecision() ? $this->getPrecision() : 0;
        $this->scale = $this->hasScale() ? $this->getScale() : 0;
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

    public function setPk($value)/*: void*/
    {
        $this->pk = $value;
    }

    public function getPk(): bool
    {
        return $this->pk;
    }

    public function setFk($value)/*: void*/
    {
        $this->fk = $value;
    }

    public function getFk(): String
    {
        return $this->fk;
    }

    public function jsonSerialize()
    {
        return array_filter([
            'name' => $this->name,
            'type' => $this->type,
            'length' => $this->length,
            'precision' => $this->precision,
            'scale' => $this->scale,
            'nullable' => $this->nullable,
            'pk' => $this->pk,
            'fk' => $this->fk,
        ]);
    }
}
