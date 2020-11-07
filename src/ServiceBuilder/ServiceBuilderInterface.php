<?php

namespace Jeto\Synclastic\ServiceBuilder;

use Elasticsearch\Client as ElasticClient;
use Jeto\Synclastic\Database\DatabaseConnectionSettings;
use Jeto\Synclastic\Database\Mapping\DatabaseMappingInterface;
use Jeto\Synclastic\Index\Builder\IndexBuilderInterface;
use Jeto\Synclastic\Index\DataFetcher\DataFetcherInterface;
use Jeto\Synclastic\Index\Definition\IndexDefinitionInterface;
use Jeto\Synclastic\Index\Refiller\IndexRefillerInterface;
use Jeto\Synclastic\Index\Synchronizer\IndexSynchronizerInterface;
use Jeto\Synclastic\Index\Updater\IndexUpdaterInterface;

interface ServiceBuilderInterface
{
    public function buildElasticClient(): ElasticClient;

    public function buildIndexDefinition(string $mappingName): IndexDefinitionInterface;

    public function buildDataFetcher(string $mappingName): DataFetcherInterface;

    public function buildIndexBuilder(string $mappingName): IndexBuilderInterface;

    public function buildIndexUpdater(string $mappingName): IndexUpdaterInterface;

    public function buildIndexRefiller(string $mappingName): IndexRefillerInterface;

    public function buildIndexSynchronizer(string $mappingName): IndexSynchronizerInterface;

    public function buildDatabaseConnectionSettings(string $mappingName): DatabaseConnectionSettings;

    public function buildDatabaseMapping(string $mappingName): DatabaseMappingInterface;
}
