<?php

namespace Jeto\Synclastic\Tests\Database;

use Jeto\Synclastic\Database\PdoFactory;

final class PdoFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $pdoFactory = new PdoFactory();

        $mysqlConnectionSettings = $this->createMysqlDatabaseConnectionSettings();
        $pdo = $pdoFactory->create($mysqlConnectionSettings);

        self::assertSame('mysql', $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME));
        
        $pgsqlConnectionSettings = $this->createPgsqlDatabaseConnectionSettings();
        $pdo = $pdoFactory->create($pgsqlConnectionSettings);

        self::assertSame('pgsql', $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME));
    }
}
