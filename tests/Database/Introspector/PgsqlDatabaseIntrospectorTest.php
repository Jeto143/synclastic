<?php

namespace Jeto\Synclastic\Tests\Database\Introspector;

use Jeto\Synclastic\Database\Introspector\PgsqlDatabaseIntrospector;
use Jeto\Synclastic\Tests\Database\TestCase as DatabaseTestCase;

final class PgsqlDatabaseIntrospectorTest extends DatabaseTestCase
{
    private const SCHEMA_NAME = 'testdb';
    private const TABLE_NAME = 'person';

    private \PDO $pdo;

    private PgsqlDatabaseIntrospector $pgsqlDatabaseIntrospector;

    protected function setUp(): void
    {
        $connectionSettings = $this->createPgsqlDatabaseConnectionSettings();
        $this->pgsqlDatabaseIntrospector = new PgsqlDatabaseIntrospector($connectionSettings);

        $dsn = sprintf('pgsql:host=%s;port=%d', self::PGSQL_HOSTNAME, self::PGSQL_PORT);
        $this->pdo = new \PDO($dsn, self::PGSQL_USERNAME, self::PGSQL_PASSWORD, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_EMULATE_PREPARES => false
        ]);

        $this->setupDbData();
    }

    protected function tearDown(): void
    {
        $this->pdo->exec(sprintf("DROP SCHEMA \"%s\" CASCADE", self::SCHEMA_NAME));
    }

    public function testFetchPrimaryKeyName(): void
    {
        self::assertSame(
            'id',
            $this->pgsqlDatabaseIntrospector->fetchPrimaryKeyName(self::SCHEMA_NAME, self::TABLE_NAME)
        );
    }

    public function testFetchColumnsTypes(): void
    {
        self::assertSame(
            ['id' => 'integer', 'name' => 'character varying', 'email' => 'character varying', 'age' => 'smallint'],
            $this->pgsqlDatabaseIntrospector->fetchColumnsTypes(self::SCHEMA_NAME, self::TABLE_NAME)
        );
    }

    private function setupDbData(): void
    {
        $this->pdo->exec(sprintf("CREATE SCHEMA \"%s\"", self::SCHEMA_NAME));
        $this->pdo->exec(sprintf(<<<SQL
            CREATE TABLE "%s"."%s" (
                "id" serial PRIMARY KEY,
                "name" VARCHAR(30) NOT NULL,
                "email" VARCHAR(50),
                "age" SMALLINT
            )
        SQL, self::SCHEMA_NAME, self::TABLE_NAME));
    }
}
