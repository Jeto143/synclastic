<?php

namespace Jeto\Synclastic\Tests\Database\TriggerCreator;

use Jeto\Synclastic\Database\TriggerCreator\DatabaseTriggerCreatorFactory;
use Jeto\Synclastic\Database\TriggerCreator\MysqlTriggerCreator;
use Jeto\Synclastic\Database\TriggerCreator\PgsqlTriggerCreator;
use Jeto\Synclastic\Tests\Database\TestCase as DatabaseTestCase;

final class DatabaseTriggerCreatorFactoryTest extends DatabaseTestCase
{
    public function testCreate(): void
    {
        $databaseTriggerCreatorFactory = new DatabaseTriggerCreatorFactory();

        $mysqlConnectionSettings = $this->createMysqlDatabaseConnectionSettings();

        self::assertInstanceOf(
            MysqlTriggerCreator::class,
            $databaseTriggerCreatorFactory->create($mysqlConnectionSettings)
        );

        $pgsqlConnectionSettings = $this->createPgsqlDatabaseConnectionSettings();

        self::assertInstanceOf(
            PgsqlTriggerCreator::class,
            $databaseTriggerCreatorFactory->create($pgsqlConnectionSettings)
        );
    }
}
