<?php

namespace Jeto\Synclastic\Database\Introspector;

use Jeto\Synclastic\Database\ConnectionSettings;

final class DatabaseInstrospectorFactory
{
    public function create(ConnectionSettings $connectionSettings): DatabaseIntrospectorInterface
    {
        $driverName = $connectionSettings->getDriverName();

        switch ($driverName) {
            case 'mysql':
                return new MysqlDatabaseIntrospector($connectionSettings);
            case 'pgsql':
                return new PgsqlDatabaseIntrospector($connectionSettings);
        }

        throw new \InvalidArgumentException("Unhandled PDO driver: {$driverName}.");
    }
}
