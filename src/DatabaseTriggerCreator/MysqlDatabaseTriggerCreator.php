<?php

namespace Jeto\Elasticize\DatabaseTriggerCreator;

use Jeto\Elasticize\DatabaseInstrospector\DatabaseIntrospectorInterface;
use Jeto\Elasticize\DatabaseInstrospector\MysqlDatabaseIntrospector;
use Jeto\Elasticize\FieldMapping\BasicFieldMappingInterface;
use Jeto\Elasticize\FieldMapping\ComputedFieldMappingInterface;
use Jeto\Elasticize\Mapping\MappingInterface;

final class MysqlDatabaseTriggerCreator implements DatabaseTriggerCreatorInterface
{
    private \PDO $pdo;
    private DatabaseIntrospectorInterface $databaseIntrospector;

    public function __construct(\PDO $pdo, DatabaseIntrospectorInterface $databaseIntrospector = null)
    {
        $this->pdo = $pdo;
        $this->databaseIntrospector = $databaseIntrospector ?? new MysqlDatabaseIntrospector($pdo);
    }

    /** @inheritDoc */
    public function createDatabaseTriggers(array $mappings, bool $forceReset = false): void
    {
        foreach ($this->computeDataChangeInsertTuples($mappings) as $databaseName => $databaseTuples) {
            $this->pdo->exec("USE {$databaseName}");

            $this->createDataChangeTable($forceReset);

            foreach ($databaseTuples as $tableName => $tuples) {
                foreach (['INSERT' => 'NEW', 'UPDATE' => 'NEW', 'DELETE' => 'OLD'] as $action => $tableAlias) {
                    $triggerName = "TR_{$tableName}_{$action}";

                    $tuplesSql = implode(",\n\t\t", $tuples);
                    $tuplesSql = preg_replace('/\bthis(?=\.)/', $tableAlias, $tuplesSql);

                    $this->pdo->exec("DROP TRIGGER IF EXISTS `{$triggerName}`");

                    $this->pdo->exec(<<<SQL
                    	CREATE TRIGGER `{$triggerName}` 
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

    private function createDataChangeTable(bool $forceReset): void
    {
        if ($forceReset) {
            $this->pdo->exec('DROP TABLE IF EXISTS data_change');
        }

        $this->pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS `data_change` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `index` VARCHAR(255) NOT NULL,
                `object_type` VARCHAR(255) NOT NULL,
                `object_id` INT NOT NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT object_unique UNIQUE (`object_type`, `object_id`) 
            )
            COLLATE='utf8_general_ci'
            ENGINE=InnoDB
            ;
        SQL);
    }

    /**
     * @param MappingInterface[] $mappings
     * @return string[][][]
     */
    private function computeDataChangeInsertTuples(array $mappings): array
    {
        $tuples = [];

        foreach ($mappings as $mapping) {
            $databaseName = $mapping->getDatabaseName();
            $tableName = $mapping->getTableName();
            $indexName = $mapping->getIndexName();

            if ($mapping->getBasicFieldsMappings()) {
                $primaryKeyName = $this->databaseIntrospector->fetchPrimaryKeyName($databaseName, $tableName);

                $tuple = "('{$indexName}', '{$tableName}', this.{$primaryKeyName})";
                $tuples[$databaseName][$tableName][] = $tuple;
            }

            foreach ($mapping->getComputedFieldsMappings() as $computedFieldMapping) {
                $targetDatabaseName = $computedFieldMapping->getTargetDatabaseName();
                $targetTableName = $computedFieldMapping->getTargetTableName();
                $ownerIdQuery = $computedFieldMapping->getOwnerIdQuery();

                $tuple = "('{$indexName}', '{$tableName}', ({$ownerIdQuery}))";
                $tuples[$targetDatabaseName][$targetTableName][] = $tuple;
            }
        }

        return $tuples;
    }
}
