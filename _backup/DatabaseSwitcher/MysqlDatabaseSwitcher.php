<?php

namespace Jeto\Sqlastic\DatabaseSwitcher;

class MysqlDatabaseSwitcher implements DatabaseSwitcherInterface
{
    public function switchToDatabase(\PDO $pdo, string $targetDatabaseName): \PDO
    {
        $pdo->exec("USE `{$targetDatabaseName}`");

        return $pdo;
    }
}
