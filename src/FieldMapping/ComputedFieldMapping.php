<?php

namespace Jeto\Elasticize\FieldMapping;

final class ComputedFieldMapping implements ComputedFieldMappingInterface
{
    private string $targetDatabaseName;

    private string $targetTableName;

    private string $valueQuery;

    private string $ownerIdQuery;

    private string $indexFieldName;

    private string $indexFieldType;

    public function __construct(
        string $targetDatabaseName,
        string $targetTableName,
        string $valueQuery,
        string $ownerIdQuery,
        string $indexFieldName,
        string $indexFieldType
    ) {
        $this->targetDatabaseName = $targetDatabaseName;
        $this->targetTableName = $targetTableName;
        $this->valueQuery = $valueQuery;
        $this->ownerIdQuery = $ownerIdQuery;
        $this->indexFieldName = $indexFieldName;
        $this->indexFieldType = $indexFieldType;
    }

    public function getTargetDatabaseName(): string
    {
        return $this->targetDatabaseName;
    }

    public function getTargetTableName(): string
    {
        return $this->targetTableName;
    }

    public function getValueQuery(): string
    {
        return $this->valueQuery;
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
        return $this->indexFieldType;
    }
}
