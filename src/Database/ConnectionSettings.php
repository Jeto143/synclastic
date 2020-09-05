<?php

namespace Jeto\Sqlastic\Database;

final class ConnectionSettings
{
    private string $driver;

    private string $hostname;

    private ?string $username;

    private ?string $password;

    public function __construct(string $driver, string $hostname, ?string $username = null, ?string $password = null)
    {
        $this->driver = $driver;
        $this->hostname = $hostname;
        $this->username = $username;
        $this->password = $password;
    }

    public function getDriverName(): string
    {
        return $this->driver;
    }

    public function getHostname(): string
    {
        return $this->hostname;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }
}
