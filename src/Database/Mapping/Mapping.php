<?php

namespace Jeto\Synclastic\Database\Mapping;

class Mapping implements MappingInterface
{
    private string $databaseName;

    private string $tableName;

    /** @var BasicFieldMappingInterface[] */
    private ?array $basicFieldsMappings;

    /** @var ComputedFieldMappingInterface[] */
    private array $computedFieldsMappings;

    public function __construct(
        string $databaseName,
        string $tableName,
        ?array $basicFieldsMappings,
        array $computedFieldsMappings
    ) {
        $this->databaseName = $databaseName;
        $this->tableName = $tableName;
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

    public function getBasicFieldsMappings(): array
    {
        return $this->basicFieldsMappings;
    }

    public function getComputedFieldsMappings(): array
    {
        return $this->computedFieldsMappings;
    }
}
