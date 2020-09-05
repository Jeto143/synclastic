<?php

namespace Jeto\Sqlastic\Database;

final class PdoFactory
{
    public function create(ConnectionSettings $connectionSettings): \PDO
    {
        $dsn = "{$connectionSettings->getDriverName()}:host={$connectionSettings->getHostname()}";

        $pdo = new \PDO($dsn, $connectionSettings->getUsername(), $connectionSettings->getPassword(), [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_EMULATE_PREPARES => false
        ]);

        if ($connectionSettings->getDriverName() === 'mysql') {
            $pdo->exec("SET SESSION SQL_MODE='ANSI_QUOTES,TRADITIONAL'");
        }

        return $pdo;
    }
}
