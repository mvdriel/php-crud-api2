<?php
namespace Com\Tqdev\CrudApi\Meta\Reflection;

use Com\Tqdev\CrudApi\Database\GenericMeta;
use Com\Tqdev\CrudApi\Meta\Definition\ColumnDefinition;

class ReflectedColumn
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

    public function __construct(GenericMeta $meta, array $columnResult)
    {
        $this->name = $columnResult['COLUMN_NAME'];
        $length = $columnResult['CHARACTER_MAXIMUM_LENGTH'] + 0;
        $this->type = $meta->getTypeConverter()->toJdbc($columnResult['DATA_TYPE'], $length);
        $this->length = $length;
        $this->precision = $columnResult['NUMERIC_PRECISION'] + 0;
        $this->scale = $columnResult['NUMERIC_SCALE'] + 0;
        $this->nullable = in_array(strtoupper($columnResult['IS_NULLABLE']), ['TRUE', 'YES', 'T', 'Y', '1']);
        $this->pk = false;
        $this->fk = '';
        $this->sanitize();
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

    public function toDefinition()
    {
        return new ColumnDefinition(
            $this->name,
            $this->type,
            $this->length,
            $this->precision,
            $this->scale,
            $this->nullable,
            $this->pk,
            $this->fk
        );
    }
}
