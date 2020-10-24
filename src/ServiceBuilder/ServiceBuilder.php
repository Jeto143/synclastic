<?php

namespace Jeto\Synclastic\ServiceBuilder;

use Elasticsearch\Client as ElasticClient;
use Elasticsearch\ClientBuilder as ElasticClientBuilder;
use Jeto\Synclastic\Database\DatabaseConnectionSettings;
use Jeto\Synclastic\Database\DataChangeFetcher\DataChangeFetcher;
use Jeto\Synclastic\Database\DataConverter\DataConverterFactory;
use Jeto\Synclastic\Database\DataFetcher\BasicDataFetcher;
use Jeto\Synclastic\Database\IndexDefinition\BasicIndexDefinitionFactory;
use Jeto\Synclastic\Database\Introspector\DatabaseInstrospectorFactory;
use Jeto\Synclastic\Database\Mapping\BasicMappingFactory;
use Jeto\Synclastic\Database\Mapping\DatabaseMappingInterface;
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
    private \stdClass $configData;

    public function __construct(\stdClass $configData)  // FIXME: actual named DTO for config
    {
        $this->configData = $configData;
    }

    public function buildElasticClient(): ElasticClient
    {
        return ElasticClientBuilder::create()->setHosts((array)$this->configData->elastic->serverUrl)->build();
    }

    public function buildIndexDefinition(string $mappingName): IndexDefinitionInterface
    {
        $mappingDesc = $this->getMappingDesc($mappingName);

        switch ($mappingDesc->type) {
            case 'database':
                $connectionSettings = $this->buildDatabaseConnectionSettings($mappingName);
                $dbIntrospector = (new DatabaseInstrospectorFactory())->create($connectionSettings);
                $databaseMapping = $this->buildDatabaseMapping($mappingName);

                return (new BasicIndexDefinitionFactory($dbIntrospector))->create(
                    $mappingDesc->indexName ?? $mappingName,
                    $databaseMapping
                );

            default:
                throw new \RuntimeException('TODO (also change exception type)');
        }
    }

     // FIXME: handle non-database mapping case (error)
    public function buildDatabaseConnectionSettings(string $mappingName): DatabaseConnectionSettings
    {
        $mappingDesc = $this->getMappingDesc($mappingName);

        $dbConnectionsDesc = (array)$this->configData->databaseConnections;

        $dbConnectionDesc = $dbConnectionsDesc[$mappingDesc->databaseConnection];

        return new DatabaseConnectionSettings(
            $dbConnectionDesc->driver,
            $dbConnectionDesc->hostname,
            $dbConnectionDesc->port ?? null,
            $dbConnectionDesc->username ?? null,
            $dbConnectionDesc->password ?? null
        );
    }

    public function buildDatabaseMapping(string $mappingName): DatabaseMappingInterface
    {
        $mappingDesc = $this->getMappingDesc($mappingName);

        switch ($mappingDesc->type) {
            case 'database':
                $connectionSettings = $this->buildDatabaseConnectionSettings($mappingName);
                $dbIntrospector = (new DatabaseInstrospectorFactory())->create($connectionSettings);

                return (new BasicMappingFactory($dbIntrospector))->create(
                    $mappingDesc->databaseName,
                    $mappingDesc->tableName,
                    $mappingDesc->indexName ?? $mappingName
                );
            default:
                throw new \RuntimeException('TODO (also change exception type)');
        }
    }

    public function buildDataFetcher(string $mappingName): DataFetcherInterface
    {
        $mappingDesc = $this->getMappingDesc($mappingName);

        switch ($mappingDesc->type) {
            case 'database':
                $databaseMapping = $this->buildDatabaseMapping($mappingName);
                $connectionSettings = $this->buildDatabaseConnectionSettings($mappingName);
                $dataConverter = (new DataConverterFactory())->create($connectionSettings);
                $dbIntrospector = (new DatabaseInstrospectorFactory())->create($connectionSettings);

                return new BasicDataFetcher($databaseMapping, $connectionSettings, $dataConverter, $dbIntrospector);

            default:
                throw new \RuntimeException('TODO (also change exception type)');
        }
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

    private function getMappingDesc(string $mappingName): \stdClass
    {
        $mappings = (array)$this->configData->mappings;

        return $mappings[$mappingName];   // FIXME: exception if not found
    }

    private function buildDataChangeFetcher(string $mappingName): DataChangeFetcherInterface
    {
        $mappingDesc = $this->getMappingDesc($mappingName);

        switch ($mappingDesc->type) {
            case 'database':
                $connectionSettings = $this->buildDatabaseConnectionSettings($mappingName);
                return new DataChangeFetcher($connectionSettings, $mappingDesc->databaseName);

            default:
                throw new \RuntimeException('TODO (also change exception type)');
        }
    }
}
