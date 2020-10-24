<?php

namespace Jeto\Synclastic\Database\Mapping;

final class NestedArrayFieldMapping implements NestedArrayFieldMappingInterface
{
    private string $targetDatabaseName;

    private string $targetTableName;

    /** @var BasicFieldMappingInterface[] */
    private array $basicFieldsMappings;

    private string $valuesQuery;

    private string $ownerIdQuery;

    private string $indexFieldName;

    public function __construct(
        string $targetDatabaseName,
        string $targetTableName,
        array $basicFieldsMappings,
        string $valuesQuery,
        string $ownerIdQuery,
        string $indexFieldName
    ) {
        $this->targetDatabaseName = $targetDatabaseName;
        $this->targetTableName = $targetTableName;
        $this->basicFieldsMappings = $basicFieldsMappings;
        $this->valuesQuery = $valuesQuery;
        $this->ownerIdQuery = $ownerIdQuery;
        $this->indexFieldName = $indexFieldName;
    }

    public function getTargetDatabaseName(): string
    {
        return $this->targetDatabaseName;
    }

    public function getTargetTableName(): string
    {
        return $this->targetTableName;
    }

    public function getBasicFieldsMappings(): array
    {
        return $this->basicFieldsMappings;
    }

    public function getValuesQuery(): string
    {
        return $this->valuesQuery;
    }

    public function getOwnerIdQuery(): string
    {
        return $this->ownerIdQuery;
    }

    public function getIndexFieldName(): string
    {
        return $this->indexFieldName;
    }

    public function getIndexFieldType(): string
    {
        return 'nested';
    }
}
