<?php

namespace Jeto\Synclastic\Tests\Database\DataChangeFetcher;

use Jeto\Synclastic\Database\DatabaseConnectionSettings;
use Jeto\Synclastic\Database\DataChangeFetcher\DataChangeFetcher;
use Jeto\Synclastic\Index\DataChange\DataChange;
use Jeto\Synclastic\Index\Definition\IndexDefinition;
use Jeto\Synclastic\Tests\Database\TestCase as DatabaseTestCase;

final class DataChangeFetcherTest extends DatabaseTestCase
{
    private const DATABASE_NAME = 'testdb';
    private const DATA_CHANGE_TABLE_NAME = 'data_change';

    private \PDO $mysqlPdo;
    private \PDO $pgsqlPdo;

    protected function setUp(): void
    {
        $this->mysqlPdo = $this->createMysqlPdo();
        $this->mysqlPdo->exec(sprintf("CREATE DATABASE `%s`", self::DATABASE_NAME));

        $this->pgsqlPdo = $this->createPgsqlPdo();
        $this->pgsqlPdo->exec(sprintf("CREATE SCHEMA \"%s\"", self::DATABASE_NAME));
    }

    protected function tearDown(): void
    {
        $this->mysqlPdo->exec(sprintf("DROP DATABASE `%s`", self::DATABASE_NAME));
        $this->pgsqlPdo->exec(sprintf("DROP SCHEMA \"%s\" CASCADE", self::DATABASE_NAME));
    }

    public function testFetchDataChangesMysql(): void
    {
        $this->mysqlPdo->exec(sprintf(<<<SQL
            CREATE TABLE IF NOT EXISTS `%s`.`%s` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `index` VARCHAR(255) NOT NULL,
                `object_type` VARCHAR(255) NOT NULL,
                `object_id` VARCHAR(255) NOT NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT object_unique UNIQUE (`object_type`, `object_id`) 
            )
            COLLATE='utf8_general_ci'
            ENGINE=InnoDB
        SQL, self::DATABASE_NAME, self::DATA_CHANGE_TABLE_NAME));

        $this->testFetchDataChanges($this->mysqlPdo, $this->createMysqlDatabaseConnectionSettings());
    }
    
    public function testFetchDataChangesPgsql(): void
    {
        $this->pgsqlPdo->exec(sprintf(<<<SQL
            CREATE TABLE IF NOT EXISTS "%s"."%s" (
                "id" SERIAL PRIMARY KEY,
                "index" VARCHAR(255) NOT NULL,
                "object_type" VARCHAR(255) NOT NULL,
                "object_id" VARCHAR(255) NOT NULL,
                CONSTRAINT object_unique UNIQUE ("object_type", "object_id") 
            )
        SQL, self::DATABASE_NAME, self::DATA_CHANGE_TABLE_NAME));

        $this->testFetchDataChanges($this->pgsqlPdo, $this->createPgsqlDatabaseConnectionSettings());
    }

    private function testFetchDataChanges(\PDO $pdo, DatabaseConnectionSettings $connectionSettings): void
    {
        $pdo->exec(sprintf(<<<SQL
            INSERT INTO "%s"."%s" 
                ("id", "index", "object_type", "object_id")
            VALUES 
                (1, 'index1', 'objectType1', 'objectId1'),
                (2, 'index2', 'objectType2', 'objectId2'),
                (3, 'index1', 'objectType3', 'objectId1'),
                (4, 'index1', 'objectType2', 123)
        SQL, self::DATABASE_NAME, self::DATA_CHANGE_TABLE_NAME));

        $dataChangeFetcher = new DataChangeFetcher($connectionSettings, self::DATABASE_NAME);

        $index1Definition = new IndexDefinition('index1', [], 'dummy');
        $index1DataChanges = $dataChangeFetcher->fetchDataChanges($index1Definition);

        self::assertCount(3, $index1DataChanges);
        self::assertContainsEquals(DataChange::create(1, 'objectType1', 'objectId1'), $index1DataChanges);
        self::assertContainsEquals(DataChange::create(3, 'objectType3', 'objectId1'), $index1DataChanges);
        self::assertContainsEquals(DataChange::create(4, 'objectType2', '123'), $index1DataChanges);

        $index2Definition = new IndexDefinition('index2', [], 'dummy');
        $index2DataChanges = $dataChangeFetcher->fetchDataChanges($index2Definition);

        self::assertCount(1, $index2DataChanges);
        self::assertContainsEquals(DataChange::create(2, 'objectType2', 'objectId2'), $index2DataChanges);
    }
}
