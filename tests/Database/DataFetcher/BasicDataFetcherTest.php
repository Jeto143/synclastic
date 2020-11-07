<?php

namespace Jeto\Synclastic\Tests\Database\DataFetcher;

use Jeto\Synclastic\Database\DataFetcher\BasicDataFetcher;
use Jeto\Synclastic\Database\Mapping\DatabaseMapping;
use Jeto\Synclastic\Index\Definition\IndexDefinition;
use Jeto\Synclastic\Tests\Database\TestCase as DatabaseTestCase;

final class BasicDataFetcherTest extends DatabaseTestCase
{
    private const DATABASE_NAME = 'testdb';

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

    public function testFetchSourceData(): void
    {
//        $databaseMapping = new DatabaseMapping(self::DATABASE_NAME, 'testtable', 'testindex', [], [], []);
//
//        $mysqlDatabaseConnectionSettings = $this->createMysqlDatabaseConnectionSettings();
//
//        $basicDataFetcher = new BasicDataFetcher($databaseMapping, $mysqlDatabaseConnectionSettings);
//
//        $indexDefinition = new IndexDefinition('index1', [], 'dummy');
//
//        $sourceData = $basicDataFetcher->fetchSourceData($indexDefinition);
    }
}
