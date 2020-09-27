<?php

namespace Jeto\Synclastic\Database\Mapping;

use Jeto\Synclastic\Database\Introspector\DatabaseIntrospectorInterface;

final class BasicMappingFactory
{
    private DatabaseIntrospectorInterface $databaseIntrospector;

    public function __construct(DatabaseIntrospectorInterface $databaseIntrospector)
    {
        $this->databaseIntrospector = $databaseIntrospector;
    }

    public function create(string $databaseName, string $tableName, string $indexName): MappingInterface
    {
        $basicFieldsMappings = $this->computeBasicFieldsMappings($databaseName, $tableName);

        return new Mapping($databaseName, $tableName, $basicFieldsMappings, []);
    }

    /**
     * @return BasicFieldMappingInterface[]
     */
    private function computeBasicFieldsMappings(string $databaseName, string $tableName): array
    {
        $columnsTypes = $this->databaseIntrospector->fetchColumnsTypes($databaseName, $tableName);

        $fieldsMappings = [];

        foreach ($columnsTypes as $columnName => $columnType) {
            $fieldsMappings[$columnName] = new BasicFieldMapping($columnName, $columnType);
        }

        return $fieldsMappings;
    }
}
