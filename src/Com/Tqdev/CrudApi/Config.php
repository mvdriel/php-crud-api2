<?php
namespace Com\Tqdev\CrudApi;

class Config {
    
    protected $values = [
        'driver' => null,
        'address' => 'localhost',
        'port' => null,
        'database' => null,
        'username' => null,
        'password' => null,
        'allowedOrigins' => '*',
    ];

    protected function getDefaultDriver(array $values): String {
        if (isset($values['driver'])) {
            return $values['driver'];
        }
        return 'mysql';
    }

    protected function getDefaultPort(String $driver): int {
        switch($driver) {
            case 'mysql': return 3306;
        }
    }

    protected function getDefaultAddress(String $driver): String {
        switch($driver) {
            case 'mysql': return 'localhost';
        }
    }

    protected function getDriverDefaults(String $driver): array {
        return [
            'driver'    => $driver,
            'address'   => $this->getDefaultAddress($driver),
            'port'      => $this->getDefaultPort($driver),
        ];
    }

    public function __construct(array $values) {
        $driver = $this->getDefaultDriver($values);
        $defaults = $this->getDriverDefaults($driver);
        $newValues = array_merge($this->values, $defaults, $values);
        $diff = array_diff_key($newValues, $this->values);
        if (!empty($diff)) {
            $key = array_keys($diff)[0];
            throw new \Exception("Config has invalid value '$key'");
        }
        $this->values = $newValues;
    }

    public function getDriver(): String {
        return $this->values['driver'];
    }
    
    public function getAddress(): String {
        return $this->values['address'];
    }
    
    public function getPort(): int {
        return $this->values['port'];
    }
    
    public function getDatabase(): String {
        return $this->values['database'];
    }
    
    public function getUsername(): String {
        return $this->values['username'];
    }
    
    public function getPassword(): String {
        return $this->values['password'];
    }
    
    public function getAllowedOrigins(): String {
        return $this->values['allowedOrigins'];
    }
}