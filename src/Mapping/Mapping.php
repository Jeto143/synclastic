<?php

namespace Jeto\Sqlastic\Mapping;

class Mapping implements MappingInterface
{
    private string $databaseName;
    private string $tableName;
    private string $indexName;
    private array $basicFieldsMappings;
    private array $computedFieldsMappings;

    public function __construct(
        string $databaseName,
        string $tableName,
        string $indexName,
        array $basicFieldsMappings,
        array $computedFieldsMappings
    )
    {
        $this->databaseName = $databaseName;
        $this->tableName = $tableName;
        $this->indexName = $indexName;
        $this->basicFieldsMappings = $basicFieldsMappings;
        $this->computedFieldsMappings = $computedFieldsMappings;
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
}
