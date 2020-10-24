<?php

namespace Jeto\Synclastic\Database;

final class PdoFactory
{
    public function create(DatabaseConnectionSettings $connectionSettings): \PDO
    {
        $pdo = new \PDO(
            $connectionSettings->getDsn(),
            $connectionSettings->getUsername(),
            $connectionSettings->getPassword(),
            [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_EMULATE_PREPARES => false
            ]
        );

        if ($connectionSettings->getDriverName() === 'mysql') {
            $pdo->exec("SET SESSION SQL_MODE='ANSI_QUOTES,TRADITIONAL'");
        }

        return $pdo;
    }
}
