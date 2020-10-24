<?php

namespace Jeto\Synclastic\Database\Introspector;

use Jeto\Synclastic\Database\DatabaseConnectionSettings;
use Jeto\Synclastic\Database\PdoFactory;

abstract class AbstractDatabaseIntrospector implements DatabaseIntrospectorInterface
{
    protected \PDO $pdo;

    public function __construct(DatabaseConnectionSettings $connectionSettings)
    {
        $this->pdo = (new PdoFactory())->create($connectionSettings);
    }
}
