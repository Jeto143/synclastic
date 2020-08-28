<?php

namespace Jeto\Elasticize\DatabaseTriggerCreator;

use Jeto\Elasticize\DatabaseInstrospector\DatabaseIntrospectorInterface;
use Jeto\Elasticize\DatabaseInstrospector\MysqlDatabaseIntrospector;

final class MysqlDatabaseTriggerCreator implements DatabaseTriggerCreatorInterface
{
    private \PDO $pdo;
    private DatabaseIntrospectorInterface $databaseIntrospector;

    public function __construct(\PDO $pdo, DatabaseIntrospectorInterface $databaseIntrospector = null)
    {
        $this->pdo = $pdo;
        $this->databaseIntrospector = $databaseIntrospector ?? new MysqlDatabaseIntrospector($pdo);
    }

    public function createDatabaseTriggers(string $databaseName, string $tableName): void
    {
        $primaryKeyName = $this->databaseIntrospector->fetchPrimaryKeyName($databaseName, $tableName);

        $this->pdo->exec("USE {$databaseName}");

        $this->createDataChangeTable();
        $this->recreateTriggers($tableName, $primaryKeyName);
    }

    private function createDataChangeTable(): void
    {
        $sql = <<<SQL
            CREATE TABLE IF NOT EXISTS `data_change` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `object_type` VARCHAR(255) NOT NULL DEFAULT '0',
                `object_id` INT NOT NULL DEFAULT '0',
                `action` ENUM('add','upd','del') NOT NULL DEFAULT 'add',
                `processed` BIT(1) NULL DEFAULT b'0',
                PRIMARY KEY (`id`)
            )
            COLLATE='utf8_general_ci'
            ENGINE=InnoDB
            ;
        SQL;

        $this->pdo->exec($sql);
    }

    private function recreateTriggers(string $tableName, string $primaryKeyName): void
    {
        $triggerName = "TR_{$tableName}_insert";
        $this->pdo->exec("DROP TRIGGER IF EXISTS `{$triggerName}`");
        $this->pdo->exec(<<<SQL
            CREATE TRIGGER `{$triggerName}` 
            AFTER INSERT ON `{$tableName}` FOR EACH ROW 
            INSERT INTO `data_change` (object_type, object_id, action) 
            VALUES ('{$tableName}', NEW.{$primaryKeyName}, 'add')
            SQL);

        $triggerName = "TR_{$tableName}_update";
        $this->pdo->exec("DROP TRIGGER IF EXISTS `{$triggerName}`");
        $this->pdo->exec(<<<SQL
            CREATE TRIGGER `{$triggerName}`
            AFTER UPDATE ON `{$tableName}` FOR EACH ROW 
            INSERT INTO `data_change` (object_type, object_id, action) 
            VALUES ('{$tableName}', OLD.{$primaryKeyName}, 'upd')
            SQL);

        $triggerName = "TR_{$tableName}_delete";
        $this->pdo->exec("DROP TRIGGER IF EXISTS `{$triggerName}`");
        $this->pdo->exec(<<<SQL
            CREATE TRIGGER `{$triggerName}` 
            AFTER DELETE ON `{$tableName}` FOR EACH ROW 
            INSERT INTO `data_change` (object_type, object_id, action) 
            VALUES ('{$tableName}', OLD.{$primaryKeyName}, 'del')
            SQL);
    }
}
