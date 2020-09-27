<?php

namespace Jeto\Synclastic\Database\TriggerCreator;

use Jeto\Synclastic\Database\ConnectionSettings;
use Jeto\Synclastic\Database\Introspector\DatabaseInstrospectorFactory;
use Jeto\Synclastic\Database\Introspector\DatabaseIntrospectorInterface;
use Jeto\Synclastic\Database\Mapping\MappingInterface;
use Jeto\Synclastic\Database\PdoFactory;

abstract class AbstractTriggerCreator implements TriggerCreatorInterface
{
    protected \PDO $pdo;
    private DatabaseIntrospectorInterface $databaseIntrospector;

    public function __construct(
        ConnectionSettings $connectionSettings,
        DatabaseIntrospectorInterface $databaseIntrospector = null
    ) {
        $this->pdo = (new PdoFactory())->create($connectionSettings);
        $this->databaseIntrospector = $databaseIntrospector
            ?? (new DatabaseInstrospectorFactory())->create($connectionSettings);
    }

    /**
     * @param MappingInterface[] $mappings
     * @return string[][][]
     */
    protected function computeDataChangeInsertTuples(array $mappings): array
    {
        $tuples = [];

        foreach ($mappings as $mapping) {
            $databaseName = $mapping->getDatabaseName();
            $tableName = $mapping->getTableName();
            $indexName = $mapping->getIndexName();

            $primaryKeyName = $this->databaseIntrospector->fetchPrimaryKeyName($databaseName, $tableName);

            $tuple = "('{$indexName}', '{$tableName}', this.{$primaryKeyName})";
            $tuples[$databaseName][$tableName][] = $tuple;

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
