<?php

namespace Jeto\Synclastic\ServiceBuilder;

use Elasticsearch\Client as ElasticClient;
use Jeto\Synclastic\Index\Builder\BuilderInterface;
use Jeto\Synclastic\Index\DataFetcher\DataFetcherInterface;
use Jeto\Synclastic\Index\Definition\DefinitionInterface;
use Jeto\Synclastic\Index\Refiller\RefillerInterface;
use Jeto\Synclastic\Index\Synchronizer\SynchronizerInterface;
use Jeto\Synclastic\Index\Updater\UpdaterInterface;

interface ServiceBuilderInterface
{
    public function buildElasticClient(): ElasticClient;

    public function buildIndexDefinition(string $mappingName): DefinitionInterface;

    public function buildIndexDataFetcher(string $mappingName): DataFetcherInterface;

    public function buildIndexBuilder(string $mappingName): BuilderInterface;

    public function buildIndexUpdater(string $mappingName): UpdaterInterface;

    public function buildIndexRefiller(string $mappingName): RefillerInterface;

    public function buildIndexSynchronizer(string $mappingName): SynchronizerInterface;
}
