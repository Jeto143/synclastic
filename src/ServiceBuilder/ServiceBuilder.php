<?php

namespace Jeto\Synclastic\ServiceBuilder;

use Elasticsearch\Client as ElasticClient;
use Elasticsearch\ClientBuilder as ElasticClientBuilder;
use Jeto\Synclastic\Database\ConnectionSettings;
use Jeto\Synclastic\Database\DataChangeFetcher\DataChangeFetcher;
use Jeto\Synclastic\Database\DataConverter\DataConverterFactory;
use Jeto\Synclastic\Database\DataFetcher\BasicDataFetcher;
use Jeto\Synclastic\Database\IndexDefinition\BasicIndexDefinitionFactory;
use Jeto\Synclastic\Database\Introspector\DatabaseInstrospectorFactory;
use Jeto\Synclastic\Database\Mapping\BasicMappingFactory;
use Jeto\Synclastic\Database\Mapping\MappingInterface;
use Jeto\Synclastic\Index\Builder\Builder;
use Jeto\Synclastic\Index\Builder\BuilderInterface;
use Jeto\Synclastic\Index\DataChange\DataChangeFetcherInterface;
use Jeto\Synclastic\Index\DataFetcher\DataFetcherInterface;
use Jeto\Synclastic\Index\Definition\DefinitionInterface;
use Jeto\Synclastic\Index\Refiller\Refiller;
use Jeto\Synclastic\Index\Refiller\RefillerInterface;
use Jeto\Synclastic\Index\Synchronizer\Synchronizer;
use Jeto\Synclastic\Index\Synchronizer\SynchronizerInterface;
use Jeto\Synclastic\Index\Updater\Updater;
use Jeto\Synclastic\Index\Updater\UpdaterInterface;

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

    public function buildIndexDefinition(string $mappingName): DefinitionInterface
    {
        $mappingDesc = $this->getMappingDesc($mappingName);

        switch ($mappingDesc->type) {
            case 'database':
                $connectionSettings = $this->createDatabaseConnectionSettings($mappingName);
                $dbIntrospector = (new DatabaseInstrospectorFactory())->create($connectionSettings);
                $databaseMapping = $this->createDatabaseMapping($mappingName);

                return (new BasicIndexDefinitionFactory($dbIntrospector))->create(
                    $mappingDesc->indexName ?? $mappingName,
                    $databaseMapping
                );

            default:
                throw new \RuntimeException('TODO (also change exception type)');
        }
    }

    public function buildIndexDataFetcher(string $mappingName): DataFetcherInterface
    {
        $mappingDesc = $this->getMappingDesc($mappingName);

        switch ($mappingDesc->type) {
            case 'database':
                $databaseMapping = $this->createDatabaseMapping($mappingName);
                $connectionSettings = $this->createDatabaseConnectionSettings($mappingName);
                $dataConverter = (new DataConverterFactory())->create($connectionSettings);
                $dbIntrospector = (new DatabaseInstrospectorFactory())->create($connectionSettings);

                return new BasicDataFetcher($databaseMapping, $connectionSettings, $dataConverter, $dbIntrospector);

            default:
                throw new \RuntimeException('TODO (also change exception type)');
        }
    }

    public function buildIndexBuilder(string $mappingName): BuilderInterface
    {
        return new Builder($this->buildElasticClient());
    }

    public function buildIndexUpdater(string $mappingName): UpdaterInterface
    {
        return new Updater($this->buildElasticClient());
    }

    public function buildIndexRefiller(string $mappingName): RefillerInterface
    {
        $elasticClient = $this->buildElasticClient();
        $dataFetcher = $this->buildIndexDataFetcher($mappingName);
        $updater = $this->buildIndexUpdater($mappingName);

        return new Refiller($elasticClient, $dataFetcher, $updater);
    }

    public function buildIndexSynchronizer(string $mappingName): SynchronizerInterface
    {
        $dataChangeFetcher = $this->createDataChangeFetcher($mappingName);
        $dataFetcher = $this->buildIndexDataFetcher($mappingName);
        $updater = $this->buildIndexUpdater($mappingName);

        return new Synchronizer($dataChangeFetcher, $dataFetcher, $updater);
    }

    private function getMappingDesc(string $mappingName): \stdClass
    {
        $mappings = (array)$this->configData->mappings;

        return $mappings[$mappingName];   // FIXME: exception if not found
    }

    private function createDataChangeFetcher(string $mappingName): DataChangeFetcherInterface
    {
        $mappingDesc = $this->getMappingDesc($mappingName);

        switch ($mappingDesc->type) {
            case 'database':
                $connectionSettings = $this->createDatabaseConnectionSettings($mappingName);
                return new DataChangeFetcher($connectionSettings, $mappingDesc->databaseName);

            default:
                throw new \RuntimeException('TODO (also change exception type)');
        }
    }

    private function createDatabaseConnectionSettings(string $mappingName): ConnectionSettings
    {
        $mappingDesc = $this->getMappingDesc($mappingName);

        $dbConnectionsDesc = (array)$this->configData->databaseConnections;

        $dbConnectionDesc = $dbConnectionsDesc[$mappingDesc->databaseConnection];

        return new ConnectionSettings(
            $dbConnectionDesc->driver,
            $dbConnectionDesc->hostname,
            $dbConnectionDesc->username ?? null,
            $dbConnectionDesc->password ?? null
        );
    }

    private function createDatabaseMapping(string $mappingName): MappingInterface
    {
        $mappingDesc = $this->getMappingDesc($mappingName);

        $connectionSettings = $this->createDatabaseConnectionSettings($mappingName);
        $dbIntrospector = (new DatabaseInstrospectorFactory())->create($connectionSettings);

        return (new BasicMappingFactory($dbIntrospector))->create(
            $mappingDesc->databaseName,
            $mappingDesc->tableName,
            $mappingDesc->indexName ?? $mappingName
        );
    }
}
