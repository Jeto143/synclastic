<?php

namespace Jeto\Sqlastic\Mapping;

use Jeto\Sqlastic\Database\Introspection\DatabaseIntrospectorInterface;
use Jeto\Sqlastic\Mapping\FieldMapping\BasicFieldMapping;

class BasicMapping implements MappingInterface
{
    private string $databaseName;
    private string $tableName;
    private string $indexName;
    private DatabaseIntrospectorInterface $databaseIntrospector;

    public function __construct(
        string $databaseName,
        string $tableName,
        string $indexName,
        DatabaseIntrospectorInterface $databaseIntrospector
    ) {
        $this->databaseName = $databaseName;
        $this->tableName = $tableName;
        $this->indexName = $indexName;
        $this->databaseIntrospector = $databaseIntrospector;
    }

    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getIndexName(): string
    {
         return $this->indexName;
    }

    public function getBasicFieldsMappings(): array
    {
        $columnsTypes = $this->databaseIntrospector->fetchColumnsTypes(
            $this->getDatabaseName(),
            $this->getTableName()
        );

        $fieldsMappings = [];

        foreach ($columnsTypes as $columnName => $columnType) {
            $fieldsMappings[$columnName] = new BasicFieldMapping($columnName, $columnType);
        }

        return $fieldsMappings;
    }

    public function getComputedFieldsMappings(): array
    {
        return [];
    }
}
