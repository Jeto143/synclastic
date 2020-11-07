<?php

namespace Jeto\Synclastic\Database\TriggerCreator;

// FIXME: make `data_change` table name configurable
final class MysqlTriggerCreator extends AbstractTriggerCreator
{
    /** @inheritDoc */
    public function createDatabaseTriggers(array $mappings, bool $forceReset = false): void
    {
        foreach ($this->computeDataChangeInsertTuples($mappings) as $databaseName => $databaseTuples) {
            $this->createDataChangeTable($databaseName, $forceReset);

            foreach ($databaseTuples as $tableName => $tuples) {
                foreach (['INSERT' => 'NEW', 'UPDATE' => 'NEW', 'DELETE' => 'OLD'] as $action => $tableAlias) {
                    $triggerName = "TR_{$tableName}_{$action}";

                    $tuplesSql = implode(",\n\t\t", $tuples);
                    $tuplesSql = preg_replace('/\bthis(?=\.)/', $tableAlias, $tuplesSql);

                    $this->pdo->exec("DROP TRIGGER IF EXISTS `{$databaseName}`.`{$triggerName}`");

                    $this->pdo->exec(<<<SQL
                        CREATE TRIGGER `{$databaseName}`.`{$triggerName}` 
                        AFTER {$action} ON `{$tableName}` FOR EACH ROW 
                        INSERT IGNORE INTO `data_change` 
                            (`index`, `object_type`, `object_id`) 
                        VALUES 
                            {$tuplesSql}
                    SQL);
                }
            }
        }
    }

    private function createDataChangeTable(string $databaseName, bool $forceReset): void
    {
        if ($forceReset) {
            $this->pdo->exec("DROP TABLE IF EXISTS `{$databaseName}`.`data_change`");
        }

        $this->pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS `{$databaseName}`.`data_change` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `index` VARCHAR(255) NOT NULL,
                `object_type` VARCHAR(255) NOT NULL,
                `object_id` INT NOT NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT object_unique UNIQUE (`object_type`, `object_id`) 
            )
            COLLATE='utf8_general_ci'
            ENGINE=InnoDB
        SQL);
    }
}
