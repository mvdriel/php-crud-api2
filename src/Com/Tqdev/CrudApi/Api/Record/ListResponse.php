<?php
namespace Com\Tqdev\CrudApi\Api\Record;

class ListResponse implements \JsonSerializable {

	protected $records;

	protected $results;

	public function __construct(array $records, int $results) {
		$this->records = $records;
		$this->results = $results;
	}

	public function getRecords(): array {
		return $this->records;
	}

	public function getResults(): int {
		return $this->results;
    }
    
    // From JsonSerializable
    public function jsonSerialize() {
        if (!$this->results) {
            return ['records' => $this->records];
        }
        return $this;
    }
}
