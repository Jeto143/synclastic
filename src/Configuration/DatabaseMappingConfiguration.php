<?php

namespace Jeto\Synclastic\Configuration;

final class DatabaseMappingConfiguration extends AbstractMappingConfiguration
{
    public const FIELD_MAPPING_STRATEGY_MANUAL = 0;
    public const FIELD_MAPPING_STRATEGY_AUTOMATIC = 1;

    private DatabaseConnectionConfiguration $databaseConnectionConfiguration;

    private string $databaseName;

    private string $tableName;

    private int $fieldMappingStrategy;

    /** @var AbstractDatabaseFieldConfiguration[] */
    private array $fieldsConfigurations;

    public function __construct(
        string $name,
        DatabaseConnectionConfiguration $databaseConnectionConfiguration,
        string $databaseName,
        string $tableName,
        int $fieldMappingStrategy,
        array $fieldsConfigurations
    ) {
        parent::__construct($name);
        $this->databaseConnectionConfiguration = $databaseConnectionConfiguration;
        $this->databaseName = $databaseName;
        $this->tableName = $tableName;
        $this->fieldMappingStrategy = $fieldMappingStrategy;
        $this->fieldsConfigurations = $fieldsConfigurations;
    }

    public function getDatabaseConnectionConfiguration(): DatabaseConnectionConfiguration
    {
        return $this->databaseConnectionConfiguration;
    }

    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getFieldMappingStrategy(): int
    {
        return $this->fieldMappingStrategy;
    }

    public function getFieldsConfigurations(): array
    {
        return $this->fieldsConfigurations;
    }
}
