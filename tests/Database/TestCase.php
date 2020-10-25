<?php

namespace Jeto\Synclastic\Tests\Database;

use Jeto\Synclastic\Database\DatabaseConnectionSettings;

class TestCase extends \PHPUnit\Framework\TestCase
{
    protected const MYSQL_USERNAME = 'root';
    protected const MYSQL_PASSWORD = 'asdf007';
    protected const MYSQL_HOSTNAME = 'mysql';
    protected const MYSQL_PORT = 3306;

    protected const PGSQL_HOSTNAME = 'pgsql';
    protected const PGSQL_PORT = 5432;
    protected const PGSQL_USERNAME = 'postgres';
    protected const PGSQL_PASSWORD = 'asdf007';

    protected function createMysqlDatabaseConnectionSettings(): DatabaseConnectionSettings
    {
        return new DatabaseConnectionSettings(
            'mysql',
            self::MYSQL_HOSTNAME,
            self::MYSQL_PORT,
            self::MYSQL_USERNAME,
            self::MYSQL_PASSWORD
        );
    }
    
    protected function createPgsqlDatabaseConnectionSettings(): DatabaseConnectionSettings
    {
        return new DatabaseConnectionSettings(
            'pgsql',
            self::PGSQL_HOSTNAME,
            self::PGSQL_PORT,
            self::PGSQL_USERNAME,
            self::PGSQL_PASSWORD
        );
    }
}
