<?php

namespace Jeto\Synclastic\Tests\Database;

use Jeto\Synclastic\Database\DatabaseConnectionSettings;
use Jeto\Synclastic\Database\Introspector\MysqlDatabaseIntrospector;
use PHPUnit\Framework\TestCase;

final class MysqlDatabaseIntrospectorTest extends TestCase
{
    private const DATABASE_NAME = 'testdb';
    private const TABLE_NAME = 'person';

    private \PDO $pdo;

    private MysqlDatabaseIntrospector $mysqlDatabaseIntrospector;

    protected function setUp(): void
    {
        $this->pdo = new \PDO('mysql:host=mysql', 'root', 'asdf007', [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_EMULATE_PREPARES => false
        ]);

        $connectionSettings = new DatabaseConnectionSettings('mysql', 'mysql', 3306, 'root', 'asdf007');
        $this->mysqlDatabaseIntrospector = new MysqlDatabaseIntrospector($connectionSettings);

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
