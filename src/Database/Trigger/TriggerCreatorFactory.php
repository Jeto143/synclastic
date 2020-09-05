<?php

namespace Jeto\Sqlastic\Database\Trigger;

final class TriggerCreatorFactory implements TriggerCreatorFactoryInterface
{
    public function create(\PDO $pdo): TriggerCreatorInterface
    {
        $driverName = $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);

        switch ($driverName) {
            case 'mysql':
                return new MysqlTriggerCreator($pdo);
        }

        throw new \InvalidArgumentException("Unhandled PDO driver: {$driverName}.");
    }
}
