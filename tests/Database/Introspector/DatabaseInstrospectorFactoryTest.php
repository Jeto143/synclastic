<?php

namespace Jeto\Synclastic\Tests\Database\Introspector;

use Jeto\Synclastic\Database\Introspector\DatabaseInstrospectorFactory;
use Jeto\Synclastic\Database\Introspector\MysqlDatabaseIntrospector;
use Jeto\Synclastic\Database\Introspector\PgsqlDatabaseIntrospector;
use Jeto\Synclastic\Tests\Database\TestCase as DatabaseTestCase;

final class DatabaseInstrospectorFactoryTest extends DatabaseTestCase
{
    public function testCreate(): void
    {
        $databaseInstrospectorFactory = new DatabaseInstrospectorFactory();

        $mysqlConnectionSettings = $this->createMysqlDatabaseConnectionSettings();

        self::assertInstanceOf(
            MysqlDatabaseIntrospector::class,
            $databaseInstrospectorFactory->create($mysqlConnectionSettings)
        );
        
        $pgsqlConnectionSettings = $this->createPgsqlDatabaseConnectionSettings();

        self::assertInstanceOf(
            PgsqlDatabaseIntrospector::class,
            $databaseInstrospectorFactory->create($pgsqlConnectionSettings)
        );
    }
}
