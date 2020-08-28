<?php

namespace Jeto\Elasticize\DatabaseInstrospector;

final class DatabaseInstrospectorFactory implements DatabaseIntrospectorFactoryInterface
{
    public function create(\PDO $pdo): DatabaseIntrospectorInterface
    {
        $driverName = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

        switch ($driverName) {
            case 'mysql':
                return new MysqlDatabaseIntrospector($pdo);
        }

        throw new \InvalidArgumentException("Unhandled PDO driver: {$driverName}.");
    }
}
