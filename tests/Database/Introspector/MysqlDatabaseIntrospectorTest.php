<?php

namespace Jeto\Synclastic\Tests\Database\Introspector;

use Jeto\Synclastic\Database\Introspector\MysqlDatabaseIntrospector;
use Jeto\Synclastic\Tests\Database\TestCase as DatabaseTestCase;

final class MysqlDatabaseIntrospectorTest extends DatabaseTestCase
{
    private const DATABASE_NAME = 'testdb';
    private const TABLE_NAME = 'person';

    private \PDO $pdo;

    private MysqlDatabaseIntrospector $mysqlDatabaseIntrospector;

    protected function setUp(): void
    {
        $connectionSettings = $this->createMysqlDatabaseConnectionSettings();
        $this->mysqlDatabaseIntrospector = new MysqlDatabaseIntrospector($connectionSettings);

        $dsn = sprintf('mysql:host=%s;port=%d', self::MYSQL_HOSTNAME, self::MYSQL_PORT);
        $this->pdo = new \PDO($dsn, self::MYSQL_USERNAME, self::MYSQL_PASSWORD, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_EMULATE_PREPARES => false
        ]);

        $this->setupDbData();
    }

    protected function tearDown(): void
    {
        $this->pdo->exec(sprintf("DROP DATABASE `%s`", self::DATABASE_NAME));
    }

    public function testFetchPrimaryKeyName(): void
    {
        self::assertSame(
            'id',
            $this->mysqlDatabaseIntrospector->fetchPrimaryKeyName(self::DATABASE_NAME, self::TABLE_NAME)
        );
    }

    public function testFetchColumnsTypes(): void
    {
        self::assertSame(
            ['id' => 'int', 'name' => 'varchar', 'email' => 'varchar', 'age' => 'tinyint'],
            $this->mysqlDatabaseIntrospector->fetchColumnsTypes(self::DATABASE_NAME, self::TABLE_NAME)
        );
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
