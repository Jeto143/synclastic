<?php

namespace Jeto\Synclastic\Configuration;

use Jeto\Synclastic\Database\DataChangeFetcher\DataChangeFetcher;
use Jeto\Synclastic\Database\DataConverter\DataConverterFactory;
use Jeto\Synclastic\Database\DataFetcher\BasicDataFetcher;
use Jeto\Synclastic\Database\IndexDefinition\BasicIndexDefinitionFactory;
use Jeto\Synclastic\Database\Introspector\DatabaseInstrospectorFactory;
use Jeto\Synclastic\Database\Introspector\DatabaseIntrospectorInterface;
use Jeto\Synclastic\Database\Mapping\BasicFieldMappingInterface;
use Jeto\Synclastic\Database\Mapping\BasicMappingFactory;
use Jeto\Synclastic\Database\Mapping\ComputedFieldMappingInterface;
use Jeto\Synclastic\Database\Mapping\DatabaseMapping;
use Jeto\Synclastic\Database\Mapping\DatabaseMappingInterface;
use Jeto\Synclastic\Database\Mapping\NestedArrayFieldMappingInterface;
use Jeto\Synclastic\Index\DataChange\DataChangeFetcherInterface;
use Jeto\Synclastic\Index\DataFetcher\DataFetcherInterface;
use Jeto\Synclastic\Index\Definition\IndexDefinitionInterface;

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
        string $mappingName,
        string $indexName,
        DatabaseConnectionConfiguration $databaseConnectionConfiguration,
        string $databaseName,
        string $tableName,
        int $fieldMappingStrategy,
        array $fieldsConfigurations
    ) {
        parent::__construct($mappingName, $indexName);
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

    public function toIndexDefinition(): IndexDefinitionInterface
    {
        $connectionSettings = $this->databaseConnectionConfiguration->toDatabaseConnectionSettings();
        $dbIntrospector = (new DatabaseInstrospectorFactory())->create($connectionSettings);

        $databaseMapping = $this->toDatabaseMapping();

        return (new BasicIndexDefinitionFactory($dbIntrospector))->create($this->indexName, $databaseMapping);
    }

    public function toDatabaseMapping(): DatabaseMapping
    {
        $connectionSettings = $this->databaseConnectionConfiguration->toDatabaseConnectionSettings();

        $dbIntrospector = (new DatabaseInstrospectorFactory())->create($connectionSettings);

        $columnsTypes = $dbIntrospector->fetchColumnsTypes($this->databaseName, $this->tableName);

        $basicFieldsMappings = $this->computeBasicFieldsMappings($columnsTypes);
        $computedFieldsMappings = $this->computeComputedFieldsMappings();
        $nestedArrayFieldsMappings = $this->computeNestedArrayFieldsMappings($dbIntrospector);

        $fieldMappingStrategy = $this->fieldMappingStrategy ?? self::FIELD_MAPPING_STRATEGY_AUTOMATIC;
        if ($fieldMappingStrategy === self::FIELD_MAPPING_STRATEGY_AUTOMATIC) {
            $basicDatabaseMapping = (new BasicMappingFactory($dbIntrospector))->create(
                $this->databaseName,
                $this->tableName,
                $this->indexName ?? $this->mappingName
            );

            $this->addAutomaticFieldsMappings($basicDatabaseMapping, $basicFieldsMappings, $computedFieldsMappings);
        }

        return new DatabaseMapping(
            $this->databaseName,
            $this->tableName,
            $this->indexName ?? $this->mappingName,
            $basicFieldsMappings,
            $computedFieldsMappings,
            $nestedArrayFieldsMappings
        );
    }

    public function toDataFetcher(): DataFetcherInterface
    {
        $databaseMapping = $this->toDatabaseMapping();
        $connectionSettings = $this->databaseConnectionConfiguration->toDatabaseConnectionSettings();
        $dataConverter = (new DataConverterFactory())->create($connectionSettings);
        $dbIntrospector = (new DatabaseInstrospectorFactory())->create($connectionSettings);

        return new BasicDataFetcher($databaseMapping, $connectionSettings, $dataConverter, $dbIntrospector);
    }

    public function toDataChangeFetcher(): DataChangeFetcherInterface
    {
        $connectionSettings = $this->databaseConnectionConfiguration->toDatabaseConnectionSettings();

        return new DataChangeFetcher($connectionSettings, $this->databaseName);
    }

    /**
     * @return BasicFieldMappingInterface[]
     */
    private function computeBasicFieldsMappings(array $columnsTypes): array
    {
        $basicFieldsMappings = [];

        /** @var DatabaseBasicFieldConfiguration[] $basicFieldsConfigurations */
        $basicFieldsConfigurations = array_filter(
            $this->fieldsConfigurations,
            static fn($fieldConfig) => $fieldConfig instanceof DatabaseBasicFieldConfiguration
        );

        foreach ($basicFieldsConfigurations as $basicFieldConfig) {
            $fieldName = $basicFieldConfig->getIndexFieldName();
            $basicFieldsMappings[$fieldName] = $basicFieldConfig->toBasicFieldMapping($columnsTypes);
        }

        return $basicFieldsMappings;
    }

    /**
     * @return ComputedFieldMappingInterface[]
     */
    private function computeComputedFieldsMappings(): array
    {
        $computedFieldsMappings = [];

        /** @var DatabaseComputedFieldConfiguration[] $computedFieldsConfigurations */
        $computedFieldsConfigurations = array_filter(
            $this->fieldsConfigurations,
            static fn($fieldConfig) => $fieldConfig instanceof DatabaseComputedFieldConfiguration
        );

        foreach ($computedFieldsConfigurations as $computedFieldConfig) {
            $fieldName = $computedFieldConfig->getIndexFieldName();
            $computedFieldsMappings[$fieldName] = $computedFieldConfig->toComputedFieldMapping();
        }

        return $computedFieldsMappings;
    }

    /**
     * @return NestedArrayFieldMappingInterface[]
     */
    private function computeNestedArrayFieldsMappings(DatabaseIntrospectorInterface $dbIntrospector): array
    {
        $nestedArrayFieldsMappings = [];

        /** @var DatabaseNestedArrayFieldConfiguration[] $nestedArrayFieldsConfigurations */
        $nestedArrayFieldsConfigurations = array_filter(
            $this->fieldsConfigurations,
            static fn($fieldConfig) => $fieldConfig instanceof DatabaseNestedArrayFieldConfiguration
        );

        foreach ($nestedArrayFieldsConfigurations as $nestedArrayFieldConfig) {
            $fieldName = $nestedArrayFieldConfig->getIndexFieldName();
            $nestedColumnsTypes = $dbIntrospector->fetchColumnsTypes(
                $nestedArrayFieldConfig->getDatabaseName(),
                $nestedArrayFieldConfig->getTableName()
            );
            $nestedArrayFieldsMappings[$fieldName]
                = $nestedArrayFieldConfig->toNestedArrayFieldMapping($nestedColumnsTypes);
        }

        return $nestedArrayFieldsMappings;
    }

    private function addAutomaticFieldsMappings(
        DatabaseMappingInterface $basicDatabaseMapping,
        array &$basicFieldsMappings,
        array &$computedFieldsMappings
    ): void {
        $ignoredFieldsNames = $this->ignoredFields ?? [];

        foreach ($basicDatabaseMapping->getBasicFieldsMappings() as $basicFieldMapping) {
            $indexFieldName = $basicFieldMapping->getIndexFieldName();
            if (!isset($basicFieldsMappings[$indexFieldName])
                && !in_array($indexFieldName, $ignoredFieldsNames, true)) {
                $basicFieldsMappings[] = $basicFieldMapping;
            }
        }
        foreach ($basicDatabaseMapping->getComputedFieldsMappings() as $computedFieldMapping) {
            $indexFieldName = $computedFieldMapping->getIndexFieldName();
            if (!isset($computedFieldsMappings[$computedFieldMapping->getIndexFieldName()])
                && !in_array($indexFieldName, $ignoredFieldsNames, true)) {
                $computedFieldsMappings[] = $computedFieldMapping;
            }
        }
    }
}
