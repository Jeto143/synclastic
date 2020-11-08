<?php

namespace Jeto\Synclastic\Configuration;

use Jeto\Synclastic\Database\DatabaseConnectionSettings;

final class DatabaseConnectionConfiguration
{
    private string $driver;

    private string $hostname;

    private ?int $port;

    private ?string $username;

    private ?string $password;

    public function __construct(string $driver, string $hostname, ?int $port, ?string $username, ?string $password)
    {
        $this->driver = $driver;
        $this->hostname = $hostname;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function getHostname(): string
    {
        return $this->hostname;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function toDatabaseConnectionSettings(): DatabaseConnectionSettings
    {
        return new DatabaseConnectionSettings(
            $this->driver,
            $this->hostname,
            $this->port,
            $this->username,
            $this->password
        );
    }
}
