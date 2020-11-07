<?php

namespace Jeto\Synclastic\Configuration;

final class DatabaseNestedArrayFieldConfiguration extends AbstractDatabaseFieldConfiguration
{
    private string $databaseName;

    private string $tableName;

    /** @var DatabaseBasicFieldConfiguration[] */
    private array $nestedFieldsConfigurations;

    private string $valuesQuery;

    private string $ownerIdQuery;

    public function __construct(
        string $indexFieldName,
        string $indexFieldType,
        string $databaseName,
        string $tableName,
        array $nestedFieldsConfigurations,
        string $valuesQuery,
        string $ownerIdQuery
    ) {
        parent::__construct($indexFieldName, $indexFieldType);
        $this->databaseName = $databaseName;
        $this->tableName = $tableName;
        $this->nestedFieldsConfigurations = $nestedFieldsConfigurations;
        $this->valuesQuery = $valuesQuery;
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

    /**
     * @return DatabaseBasicFieldConfiguration[]
     */
    public function getNestedFieldsConfigurations(): array
    {
        return $this->nestedFieldsConfigurations;
    }

    public function getValuesQuery(): string
    {
        return $this->valuesQuery;
    }

    public function getOwnerIdQuery(): string
    {
        return $this->ownerIdQuery;
    }
}
