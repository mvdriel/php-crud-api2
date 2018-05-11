<?php
namespace Com\Tqdev\CrudApi\Data\Record;

class ListResponse implements \JsonSerializable
{

    private $records;

    private $results;

    public function __construct(array $records, int $results)
    {
        $this->records = $records;
        $this->results = $results;
    }

    public function getRecords(): array
    {
        return $this->records;
    }

    public function getResults(): int
    {
        return $this->results;
    }

    public function jsonSerialize()
    {
        $result = ['records' => $this->records];
        if ($this->results) {
            $result['results'] = $this->results;
        }
        return $result;
    }
}
