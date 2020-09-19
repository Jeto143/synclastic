<?php

namespace Jeto\Sqlastic\Database\Trigger;

use Jeto\Sqlastic\Database\ConnectionSettings;

final class TriggerCreatorFactory
{
    public function create(ConnectionSettings $connectionSettings): TriggerCreatorInterface
    {
        $driverName = $connectionSettings->getDriverName();

        switch ($driverName) {
            case 'mysql':
                return new MysqlTriggerCreator($connectionSettings);
            case 'pgsql':
                return new PgsqlTriggerCreator($connectionSettings);
        }

        throw new \InvalidArgumentException("Unhandled PDO driver: {$driverName}.");
    }
}
