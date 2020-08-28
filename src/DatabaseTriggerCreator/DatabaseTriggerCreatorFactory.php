<?php

namespace Jeto\Elasticize\DatabaseTriggerCreator;

final class DatabaseTriggerCreatorFactory implements DatabaseTriggerCreatorFactoryInterface
{
    public function create(\PDO $pdo): DatabaseTriggerCreatorInterface
    {
        $driverName = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

        switch ($driverName) {
            case 'mysql':
                return new MysqlDatabaseTriggerCreator($pdo);
        }

        throw new \InvalidArgumentException("Unhandled PDO driver: {$driverName}.");
    }
}
