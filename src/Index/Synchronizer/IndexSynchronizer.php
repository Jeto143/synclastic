<?php

namespace Jeto\Synclastic\Index\Synchronizer;

use Jeto\Synclastic\Index\DataChange\DataChange;
use Jeto\Synclastic\Index\DataChange\DataChangeFetcherInterface;
use Jeto\Synclastic\Index\DataFetcher\DataFetcherInterface;
use Jeto\Synclastic\Index\Definition\IndexDefinitionInterface;
use Jeto\Synclastic\Index\Updater\IndexUpdaterInterface;

final class IndexSynchronizer implements IndexSynchronizerInterface
{
    private DataChangeFetcherInterface $dataChangeFetcher;

    private DataFetcherInterface $dataFetcher;

    private IndexUpdaterInterface $updater;

    public function __construct(
        DataChangeFetcherInterface $dataChangeFetcher,
        DataFetcherInterface $dataFetcher,
        IndexUpdaterInterface $updater
    ) {
        $this->dataChangeFetcher = $dataChangeFetcher;
        $this->dataFetcher = $dataFetcher;
        $this->updater = $updater;
    }

    public function synchronizeDocuments(IndexDefinitionInterface $indexDefinition): array
    {
        $dataChanges = $this->dataChangeFetcher->fetchDataChanges($indexDefinition);
        if (!$dataChanges) {
            return [];
        }

        $changedObjectIds = array_map(static fn(DataChange $dataChange) => $dataChange->getObjectId(), $dataChanges);

        $operations = $this->synchronizeDocumentsByIds($indexDefinition, $changedObjectIds);

        $this->dataChangeFetcher->onDataChangesProcessed($dataChanges);

        return $operations;
    }

    public function synchronizeDocumentsByIds(IndexDefinitionInterface $indexDefinition, array $identifiers): array
    {
        $documentsData = $this->dataFetcher->fetchSourceData($indexDefinition, $identifiers);

        return $this->updater->synchronizeDocuments($indexDefinition, $documentsData);
    }
}
