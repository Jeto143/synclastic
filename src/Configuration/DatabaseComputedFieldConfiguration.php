<?php

namespace Jeto\Synclastic\Configuration;

final class DatabaseComputedFieldConfiguration extends AbstractDatabaseFieldConfiguration
{
    private string $databaseName;

    private string $tableName;

    private string $valueQuery;

    private string $ownerIdQuery;

    public function __construct(
        string $indexFieldName,
        string $indexFieldType,
        string $databaseName,
        string $tableName,
        string $valueQuery,
        string $ownerIdQuery
    ) {
        parent::__construct($indexFieldName, $indexFieldType);
        $this->databaseName = $databaseName;
        $this->tableName = $tableName;
        $this->valueQuery = $valueQuery;
        $this->ownerIdQuery = $ownerIdQuery;
    }

    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getValueQuery(): string
    {
        return $this->valueQuery;
    }

    public function getOwnerIdQuery(): string
    {
        return $this->ownerIdQuery;
    }
}
