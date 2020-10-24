<?php

namespace Jeto\Synclastic\Database\TriggerCreator;

use Jeto\Synclastic\Database\DatabaseConnectionSettings;
use Jeto\Synclastic\Database\TriggerCreator\MysqlTriggerCreator;
use Jeto\Synclastic\Database\TriggerCreator\PgsqlTriggerCreator;
use Jeto\Synclastic\Database\TriggerCreator\TriggerCreatorInterface;

final class DatabaseTriggerCreatorFactory
{
    public function create(DatabaseConnectionSettings $connectionSettings): TriggerCreatorInterface
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
