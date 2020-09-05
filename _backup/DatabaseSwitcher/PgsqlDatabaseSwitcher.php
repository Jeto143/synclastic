<?php

namespace Jeto\Sqlastic\DatabaseSwitcher;

class PgsqlDatabaseSwitcher implements DatabaseSwitcherInterface
{
    public function switchToDatabase(\PDO $pdo, string $targetDatabaseName): \PDO
    {
        // TODO: Implement switchToDatabase() method.
    }
}
