<?php

namespace Jeto\Synclastic\Index\Refiller;

use Elasticsearch\Client as ElasticClient;
use Jeto\Synclastic\Index\DataFetcher\DataFetcherInterface;
use Jeto\Synclastic\Index\Definition\IndexDefinitionInterface;
use Jeto\Synclastic\Index\Operation\IndexOperation;
use Jeto\Synclastic\Index\Updater\IndexUpdaterInterface;

final class IndexRefiller implements IndexRefillerInterface
{
    private ElasticClient $elastic;

    private DataFetcherInterface $dataFetcher;

    private IndexUpdaterInterface $indexUpdater;

    public function __construct(
        ElasticClient $elastic,
        DataFetcherInterface $dataFetcher,
        IndexUpdaterInterface $indexUpdater
    ) {
        $this->elastic = $elastic;
        $this->dataFetcher = $dataFetcher;
        $this->indexUpdater = $indexUpdater;
    }

    public function refillIndex(IndexDefinitionInterface $indexDefinition): array
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
     * @return IndexOperation[]
     */
    private function updateIndex(IndexDefinitionInterface $indexDefinition): array
    {
        $documentsData = $this->dataFetcher->fetchSourceData($indexDefinition);

        return $this->indexUpdater->addDocuments($indexDefinition, $documentsData);
    }
}
