<?php

namespace Jeto\Sqlastic\Database\Introspector;

use Jeto\Sqlastic\Database\ConnectionSettings;
use Jeto\Sqlastic\Database\PdoFactory;

abstract class AbstractDatabaseIntrospector implements DatabaseIntrospectorInterface
{
    protected \PDO $pdo;

    public function __construct(ConnectionSettings $connectionSettings)
    {
        $this->pdo = (new PdoFactory())->create($connectionSettings);
    }
}
