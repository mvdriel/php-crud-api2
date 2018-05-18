<?php
namespace Com\Tqdev\CrudApi;

class Config
{
    private $values = [
        'driver' => null,
        'address' => 'localhost',
        'port' => null,
        'database' => null,
        'username' => null,
        'password' => null,
        'allowedOrigins' => '*',
        'debug' => false,
        'cacheType' => 'TempFile',
        'cachePath' => '',
        'cacheTime' => 10,
    ];

    private function getDefaultDriver(array $values): String
    {
        if (isset($values['driver'])) {
            return $values['driver'];
        }
        return 'mysql';
    }

    private function getDefaultPort(String $driver): int
    {
        switch ($driver) {
            case 'mysql':return 3306;
            case 'pgsql':return 5432;
            case 'sqlsrv':return 1433;
        }
    }

    private function getDefaultAddress(String $driver): String
    {
        switch ($driver) {
            case 'mysql':return 'localhost';
            case 'pgsql':return 'localhost';
            case 'sqlsrv':return 'localhost';
        }
    }

    private function getDriverDefaults(String $driver): array
    {
        return [
            'driver' => $driver,
            'address' => $this->getDefaultAddress($driver),
            'port' => $this->getDefaultPort($driver),
        ];
    }

    public function __construct(array $values)
    {
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

    public function getDriver(): String
    {
        return $this->values['driver'];
    }

    public function getAddress(): String
    {
        return $this->values['address'];
    }

    public function getPort(): int
    {
        return $this->values['port'];
    }

    public function getDatabase(): String
    {
        return $this->values['database'];
    }

    public function getUsername(): String
    {
        return $this->values['username'];
    }

    public function getPassword(): String
    {
        return $this->values['password'];
    }

    public function getAllowedOrigins(): String
    {
        return $this->values['allowedOrigins'];
    }

    public function getDebug(): String
    {
        return $this->values['debug'];
    }

    public function getCacheType(): String
    {
        return $this->values['cacheType'];
    }

    public function getCachePath(): String
    {
        return $this->values['cachePath'];
    }

    public function getCacheTime(): int
    {
        return $this->values['cacheTime'];
    }
}
