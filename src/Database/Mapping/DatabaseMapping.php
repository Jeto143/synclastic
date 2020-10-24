<?php

namespace Jeto\Synclastic\Database\Mapping;

class DatabaseMapping implements DatabaseMappingInterface
{
    private string $databaseName;

    private string $tableName;

    private string $indexName;

    /** @var BasicFieldMappingInterface[] */
    private array $basicFieldsMappings;

    /** @var ComputedFieldMappingInterface[] */
    private array $computedFieldsMappings;

    /** @var NestedArrayFieldMappingInterface[] */
    private array $nestedArrayFieldsMappings;

    public function __construct(
        string $databaseName,
        string $tableName,
        string $indexName,
        array $basicFieldsMappings,
        array $computedFieldsMappings,
        array $nestedArrayFieldsMappings
    ) {
        $this->databaseName = $databaseName;
        $this->tableName = $tableName;
        $this->indexName = $indexName;
        $this->basicFieldsMappings = $basicFieldsMappings;
        $this->computedFieldsMappings = $computedFieldsMappings;
        $this->nestedArrayFieldsMappings = $nestedArrayFieldsMappings;
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
        return $this->basicFieldsMappings;
    }

    public function getComputedFieldsMappings(): array
    {
        return $this->computedFieldsMappings;
    }

    public function getNestedArrayFieldsMappings(): array
    {
        return $this->nestedArrayFieldsMappings;
    }
}
