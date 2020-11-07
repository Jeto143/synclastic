<?php

namespace Jeto\Synclastic\Tests\Database\TriggerCreator;

use Jeto\Synclastic\Database\Mapping\DatabaseMapping;
use Jeto\Synclastic\Database\TriggerCreator\MysqlTriggerCreator;
use Jeto\Synclastic\Tests\Database\TestCase as DatabaseTestCase;

final class MysqlTriggerCreatorTest extends DatabaseTestCase
{
    private const DATABASE_NAME = 'testdb';
    private const TABLE_NAME = 'person';

    private \PDO $pdo;

    private MysqlTriggerCreator $mysqlTriggerCreator;

    protected function setUp(): void
    {
        $this->pdo = $this->createMysqlPdo();
        $connectionSettings = $this->createMysqlDatabaseConnectionSettings();
        $this->mysqlTriggerCreator = new MysqlTriggerCreator($connectionSettings);

        $this->setupDbData();
    }

    protected function tearDown(): void
    {
        $this->pdo->exec(sprintf("DROP DATABASE `%s`", self::DATABASE_NAME));
    }

    public function testCreateDatabaseTriggers(): void
    {
//        $databaseMapping = new DatabaseMapping(self::DATABASE_NAME, self::TABLE_NAME, 'testindex', [], [], []);
//        $this->mysqlTriggerCreator->createDatabaseTriggers([$databaseMapping]);
    }

    private function setupDbData(): void
    {
        $this->pdo->exec(sprintf("CREATE DATABASE `%s`", self::DATABASE_NAME));
        $this->pdo->exec(sprintf(<<<SQL
            CREATE TABLE `%s`.`%s` (
                `id` INT(6) AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(30) NOT NULL,
                `email` VARCHAR(50),
                `age` TINYINT UNSIGNED
            )
        SQL, self::DATABASE_NAME, self::TABLE_NAME));
    }
}
