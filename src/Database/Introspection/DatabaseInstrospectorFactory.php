<?php

namespace Jeto\Sqlastic\Database\Introspection;

use Jeto\Sqlastic\Database\ConnectionSettings;

final class DatabaseInstrospectorFactory implements DatabaseIntrospectorFactoryInterface
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
