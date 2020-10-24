<?php

namespace Jeto\Synclastic\Database;

final class DatabaseConnectionSettings
{
    private string $driverName;

    private string $hostname;

    private ?int $port;

    private ?string $username;

    private ?string $password;

    public function __construct(
        string $driver,
        string $hostname,
        ?int $port = null,
        ?string $username = null,
        ?string $password = null
    ) {
        $this->driverName = $driver;
        $this->hostname = $hostname;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
    }

    public function getDriverName(): string
    {
        return $this->driverName;
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

    public function getDsn(): string
    {
        return "{$this->driverName}:host={$this->hostname};port={$this->port}";
    }

    public function __toString(): string
    {
        return $this->getDsn() . ";username={$this->username};password={$this->password}";
    }
}
