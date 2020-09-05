<?php

namespace Jeto\Sqlastic\DatabaseSwitcher;

interface DatabaseSwitcherInterface
{
    public function switchToDatabase(\PDO $pdo, string $targetDatabaseName): \PDO;
}
