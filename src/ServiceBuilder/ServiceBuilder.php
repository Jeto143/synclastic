<?php

namespace Jeto\Synclastic\ServiceBuilder;

use Elasticsearch\Client as ElasticClient;
use Elasticsearch\ClientBuilder as ElasticClientBuilder;
use Jeto\Synclastic\Configuration\AbstractMappingConfiguration;
use Jeto\Synclastic\Configuration\Configuration;
use Jeto\Synclastic\Configuration\DatabaseBasicFieldConfiguration;
use Jeto\Synclastic\Configuration\DatabaseComputedFieldConfiguration;
use Jeto\Synclastic\Configuration\DatabaseMappingConfiguration;
use Jeto\Synclastic\Configuration\DatabaseNestedArrayFieldConfiguration;
use Jeto\Synclastic\Database\DatabaseConnectionSettings;
use Jeto\Synclastic\Database\DataChangeFetcher\DataChangeFetcher;
use Jeto\Synclastic\Database\DataConverter\DataConverterFactory;
use Jeto\Synclastic\Database\DataFetcher\BasicDataFetcher;
use Jeto\Synclastic\Database\IndexDefinition\BasicIndexDefinitionFactory;
use Jeto\Synclastic\Database\Introspector\DatabaseInstrospectorFactory;
use Jeto\Synclastic\Database\Mapping\BasicFieldMapping;
use Jeto\Synclastic\Database\Mapping\BasicMappingFactory;
use Jeto\Synclastic\Database\Mapping\ComputedFieldMapping;
use Jeto\Synclastic\Database\Mapping\DatabaseMapping;
use Jeto\Synclastic\Database\Mapping\DatabaseMappingInterface;
use Jeto\Synclastic\Database\Mapping\NestedArrayFieldMapping;
use Jeto\Synclastic\Database\TriggerCreator\DatabaseTriggerCreatorFactory;
use Jeto\Synclastic\Database\TriggerCreator\TriggerCreatorInterface;
use Jeto\Synclastic\Index\Builder\IndexBuilder;
use Jeto\Synclastic\Index\Builder\IndexBuilderInterface;
use Jeto\Synclastic\Index\DataChange\DataChangeFetcherInterface;
use Jeto\Synclastic\Index\DataFetcher\DataFetcherInterface;
use Jeto\Synclastic\Index\Definition\IndexDefinitionInterface;
use Jeto\Synclastic\Index\Refiller\IndexRefiller;
use Jeto\Synclastic\Index\Refiller\IndexRefillerInterface;
use Jeto\Synclastic\Index\Synchronizer\IndexSynchronizer;
use Jeto\Synclastic\Index\Synchronizer\IndexSynchronizerInterface;
use Jeto\Synclastic\Index\Updater\IndexUpdater;
use Jeto\Synclastic\Index\Updater\IndexUpdaterInterface;

final class ServiceBuilder implements ServiceBuilderInterface
{
    private Configuration $configuration;

    public function __construct(Configuration $configuration)  // FIXME: actual named DTO for config
    {
        $this->configuration = $configuration;
    }

    public function buildElasticClient(): ElasticClient
    {
        $serverUrl = $this->configuration->getElasticConfiguration()->getServerUrl();

        return ElasticClientBuilder::create()->setHosts((array)$serverUrl)->build();
    }

    public function buildIndexDefinition(string $mappingName): IndexDefinitionInterface
    {
        $mappingConfiguration = $this->getMappingConfiguration($mappingName);

        if ($mappingConfiguration instanceof DatabaseMappingConfiguration) {
            $connectionSettings = $this->buildDatabaseConnectionSettings($mappingName);
            $dbIntrospector = (new DatabaseInstrospectorFactory())->create($connectionSettings);
            $databaseMapping = $this->buildDatabaseMapping($mappingName);

            return (new BasicIndexDefinitionFactory($dbIntrospector))->create(
                $mappingConfiguration->indexName ?? $mappingName,
                $databaseMapping
            );
        }

        throw new \RuntimeException('TODO (also change exception type)');
    }

     // FIXME: handle non-database mapping case (error)
    public function buildDatabaseConnectionSettings(string $mappingName): DatabaseConnectionSettings
    {
        /** @var DatabaseMappingConfiguration $mappingConfiguration */
        $mappingConfiguration = $this->getMappingConfiguration($mappingName);

        $databaseConnectionConfiguration = $mappingConfiguration->getDatabaseConnectionConfiguration();

        return new DatabaseConnectionSettings(
            $databaseConnectionConfiguration->getDriver(),
            $databaseConnectionConfiguration->getHostname(),
            $databaseConnectionConfiguration->getPort(),
            $databaseConnectionConfiguration->getUsername(),
            $databaseConnectionConfiguration->getPassword()
        );
    }

    // FIXME: mess
    public function buildDatabaseMapping(string $mappingName): DatabaseMappingInterface
    {
        $connectionSettings = $this->buildDatabaseConnectionSettings($mappingName);
        $dbIntrospector = (new DatabaseInstrospectorFactory())->create($connectionSettings);

        /** @var DatabaseMappingConfiguration $mappingConfiguration */
        $mappingConfiguration = $this->getMappingConfiguration($mappingName);

        $columnsTypes = $dbIntrospector->fetchColumnsTypes(
            $mappingConfiguration->getDatabaseName(),
            $mappingConfiguration->getTableName()
        );

        $basicFieldsMappings = [];
        $computedFieldsMappings = [];
        $nestedArrayFieldsMappings = [];

        $fieldMappingStrategy = $mappingConfiguration->getFieldMappingStrategy()
            ?? DatabaseMappingConfiguration::FIELD_MAPPING_STRATEGY_AUTOMATIC;

        foreach ($mappingConfiguration->getFieldsConfigurations() as $fieldConfiguration) {
            if ($fieldConfiguration instanceof DatabaseComputedFieldConfiguration) {
                $computedFieldsMappings[$fieldConfiguration->getIndexFieldName()] = new ComputedFieldMapping(
                    $fieldConfiguration->getDatabaseName(),
                    $fieldConfiguration->getTableName(),
                    $fieldConfiguration->getValueQuery(),
                    $fieldConfiguration->getOwnerIdQuery(),
                    $fieldConfiguration->getIndexFieldName(),
                    $fieldConfiguration->getIndexFieldType()
                );
            } elseif ($fieldConfiguration instanceof DatabaseNestedArrayFieldConfiguration) {
                $nestedBasicFieldsMappings = [];
                foreach ($fieldConfiguration->getNestedFieldsConfigurations() as $nestedFieldConfiguration) {
                    $targetTableColumnsTypes = $dbIntrospector->fetchColumnsTypes(
                        $fieldConfiguration->getDatabaseName(),
                        $fieldConfiguration->getTableName()
                    );
                    $columnName = $nestedFieldDesc->columnName ?? $nestedFieldConfiguration->getIndexFieldName();
                    $nestedBasicFieldsMappings[] = new BasicFieldMapping(
                        $columnName,
                        $targetTableColumnsTypes[$columnName],
                        $nestedFieldConfiguration->getIndexFieldType()
                    );
                }

                $nestedArrayFieldsMappings[$fieldConfiguration->getIndexFieldName()] = new NestedArrayFieldMapping(
                    $fieldConfiguration->getDatabaseName(),
                    $fieldConfiguration->getTableName(),
                    $nestedBasicFieldsMappings,
                    $fieldConfiguration->getValuesQuery(),
                    $fieldConfiguration->getOwnerIdQuery(),
                    $fieldConfiguration->getIndexFieldName()
                );
            } elseif ($fieldConfiguration instanceof DatabaseBasicFieldConfiguration) {
                $columnName = $fieldConfiguration->columnName ?? $fieldConfiguration->getIndexFieldName();
                $basicFieldsMappings[$fieldConfiguration->getIndexFieldName()] = new BasicFieldMapping(
                    $columnName,
                    $columnsTypes[$columnName],
                    $fieldConfiguration->getIndexFieldType()
                );
            }
        }

        if ($fieldMappingStrategy === DatabaseMappingConfiguration::FIELD_MAPPING_STRATEGY_AUTOMATIC) {
            $ignoredFieldsNames = $mappingConfiguration->ignoredFields ?? [];

            $basicDatabaseMapping = (new BasicMappingFactory($dbIntrospector))->create(
                $mappingConfiguration->getDatabaseName(),
                $mappingConfiguration->getTableName(),
                $mappingConfiguration->indexName ?? $mappingName
            );

            foreach ($basicDatabaseMapping->getBasicFieldsMappings() as $basicFieldMapping) {
                $indexFieldName = $basicFieldMapping->getIndexFieldName();
                if (!isset($basicFieldsMappings[$indexFieldName])
                        && !in_array($indexFieldName, $ignoredFieldsNames, true)) {
                    $basicFieldsMappings[] = $basicFieldMapping;
                }
            }
            foreach ($basicDatabaseMapping->getComputedFieldsMappings() as $computedFieldMapping) {
                $indexFieldName = $computedFieldMapping->getIndexFieldName();
                if (!isset($computedFieldMappings[$computedFieldMapping->getIndexFieldName()])
                        && !in_array($indexFieldName, $ignoredFieldsNames, true)) {
                    $computedFieldMappings[] = $computedFieldMapping;
                }
            }
        }

        return new DatabaseMapping(
            $mappingConfiguration->getDatabaseName(),
            $mappingConfiguration->getTableName(),
            $mappingConfiguration->indexName ?? $mappingName,
            $basicFieldsMappings,
            $computedFieldsMappings,
            $nestedArrayFieldsMappings
        );
    }

    public function buildDataFetcher(string $mappingName): DataFetcherInterface
    {
        $mappingConfiguration = $this->getMappingConfiguration($mappingName);

        if ($mappingConfiguration instanceof DatabaseMappingConfiguration) {
            $databaseMapping = $this->buildDatabaseMapping($mappingName);
            $connectionSettings = $this->buildDatabaseConnectionSettings($mappingName);
            $dataConverter = (new DataConverterFactory())->create($connectionSettings);
            $dbIntrospector = (new DatabaseInstrospectorFactory())->create($connectionSettings);

            return new BasicDataFetcher($databaseMapping, $connectionSettings, $dataConverter, $dbIntrospector);
        }

        throw new \RuntimeException('TODO (also change exception type)');
    }

    public function buildIndexBuilder(string $mappingName): IndexBuilderInterface
    {
        return new IndexBuilder($this->buildElasticClient());
    }

    public function buildIndexUpdater(string $mappingName): IndexUpdaterInterface
    {
        return new IndexUpdater($this->buildElasticClient());
    }

    public function buildIndexRefiller(string $mappingName): IndexRefillerInterface
    {
        $elasticClient = $this->buildElasticClient();
        $dataFetcher = $this->buildDataFetcher($mappingName);
        $updater = $this->buildIndexUpdater($mappingName);

        return new IndexRefiller($elasticClient, $dataFetcher, $updater);
    }

    public function buildIndexSynchronizer(string $mappingName): IndexSynchronizerInterface
    {
        $dataChangeFetcher = $this->buildDataChangeFetcher($mappingName);
        $dataFetcher = $this->buildDataFetcher($mappingName);
        $updater = $this->buildIndexUpdater($mappingName);

        return new IndexSynchronizer($dataChangeFetcher, $dataFetcher, $updater);
    }

    private function getMappingConfiguration(string $mappingName): AbstractMappingConfiguration
    {
        foreach ($this->configuration->getMappingsConfigurations() as $mappingConfiguration) {
            if ($mappingConfiguration->getName() === $mappingName) {
                return $mappingConfiguration;
            }
        }

        throw new \InvalidArgumentException("No such mapping: {$mappingName}.");
    }

    private function buildDataChangeFetcher(string $mappingName): DataChangeFetcherInterface
    {
        $mappingConfiguration = $this->getMappingConfiguration($mappingName);

        if ($mappingConfiguration instanceof DatabaseMappingConfiguration) {
            $connectionSettings = $this->buildDatabaseConnectionSettings($mappingName);

            return new DataChangeFetcher($connectionSettings, $mappingConfiguration->getDatabaseName());
        }

        throw new \RuntimeException('TODO (also change exception type)');
    }
}
