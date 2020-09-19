<?php

namespace Jeto\Sqlastic\Index\Populator;

use Elasticsearch\Client as ElasticClient;
use Jeto\Sqlastic\Index\Updater\IndexUpdaterInterface;
use Jeto\Sqlastic\Mapping\MappingInterface;

final class IndexPopulator implements IndexPopulatorInterface
{
    private ElasticClient $elastic;
    private IndexUpdaterInterface $indexUpdater;

    public function __construct(ElasticClient $elastic, IndexUpdaterInterface $indexUpdater)
    {
        $this->elastic = $elastic;
        $this->indexUpdater = $indexUpdater;
    }

    public function populateIndex(MappingInterface $mapping): void
    {
        $this->clearIndex($mapping->getIndexName());
        $this->updateIndex($mapping);
    }

    private function clearIndex(string $indexName): void
    {
        $this->elastic->deleteByQuery(
            [
                'index' => $indexName,
                'body' => [
                    'query' => [
                        'match_all' => (object)[]
                    ]
                ]
            ]
        );
    }

    private function updateIndex(MappingInterface $mapping): void
    {
        $identifiers = [];

        foreach ($mapping->fetchIndexData() as $documentData) {
            $identifiers[] = $documentData[$mapping->getIdentifierFieldName()];
        }

        $this->indexUpdater->updateDocuments($mapping, $identifiers);
    }
}
