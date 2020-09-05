<?php

namespace Jeto\Sqlastic\Database\Trigger;

use Jeto\Sqlastic\Database\ConnectionSettings;
use Jeto\Sqlastic\Database\Introspection\DatabaseInstrospectorFactory;
use Jeto\Sqlastic\Database\Introspection\DatabaseIntrospectorInterface;
use Jeto\Sqlastic\Database\PdoFactory;
use Jeto\Sqlastic\Mapping\MappingInterface;

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
