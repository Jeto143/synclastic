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
    
    protected function createMysqlPdo(): \PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%d', self::MYSQL_HOSTNAME, self::MYSQL_PORT);
        
        $pdo = new \PDO($dsn, self::MYSQL_USERNAME, self::MYSQL_PASSWORD, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_EMULATE_PREPARES => false
        ]);

        $pdo->exec("SET SESSION SQL_MODE='ANSI_QUOTES,TRADITIONAL'");

        return $pdo;
    }
    
    protected function createPgsqlPdo(): \PDO
    {
        $dsn = sprintf('pgsql:host=%s;port=%d', self::PGSQL_HOSTNAME, self::PGSQL_PORT);
        
        $pdo = new \PDO($dsn, self::PGSQL_USERNAME, self::PGSQL_PASSWORD, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_EMULATE_PREPARES => false
        ]);

        return $pdo;
    }
}
