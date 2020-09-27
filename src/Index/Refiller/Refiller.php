<?php

namespace Jeto\Synclastic\Index\Refiller;

use Elasticsearch\Client as ElasticClient;
use Jeto\Synclastic\Index\DataFetcher\DataFetcherInterface;
use Jeto\Synclastic\Index\Definition\DefinitionInterface;
use Jeto\Synclastic\Index\Operation\Operation;
use Jeto\Synclastic\Index\Updater\UpdaterInterface;

final class Refiller implements RefillerInterface
{
    private ElasticClient $elastic;

    private DataFetcherInterface $dataFetcher;

    private UpdaterInterface $indexUpdater;

    public function __construct(
        ElasticClient $elastic,
        DataFetcherInterface $dataFetcher,
        UpdaterInterface $indexUpdater
    ) {
        $this->elastic = $elastic;
        $this->dataFetcher = $dataFetcher;
        $this->indexUpdater = $indexUpdater;
    }

    public function refillIndex(DefinitionInterface $indexDefinition): array
    {
        $this->clearIndex($indexDefinition->getIndexName());
        return $this->updateIndex($indexDefinition);
    }

    private function clearIndex(string $indexName): void
    {
        $this->elastic->deleteByQuery([
            'index' => $indexName,
            'body' => [
                'query' => [
                    'match_all' => (object)[]
                ]
            ]
        ]);
    }

    /**
     * @return Operation[]
     */
    private function updateIndex(DefinitionInterface $indexDefinition): array
    {
        $documentsData = $this->dataFetcher->fetchSourceData($indexDefinition);

        return $this->indexUpdater->addDocuments($indexDefinition, $documentsData);
    }
}
