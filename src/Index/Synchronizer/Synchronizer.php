<?php

namespace Jeto\Synclastic\Index\Synchronizer;

use Jeto\Synclastic\Index\DataChange\DataChange;
use Jeto\Synclastic\Index\DataChange\DataChangeFetcherInterface;
use Jeto\Synclastic\Index\DataFetcher\DataFetcherInterface;
use Jeto\Synclastic\Index\Definition\DefinitionInterface;
use Jeto\Synclastic\Index\Updater\UpdaterInterface;

final class Synchronizer implements SynchronizerInterface
{
    private DataChangeFetcherInterface $dataChangeFetcher;

    private DataFetcherInterface $fetcher;

    private UpdaterInterface $updater;

    public function __construct(
        DataChangeFetcherInterface $dataChangeFetcher,
        DataFetcherInterface $fetcher,
        UpdaterInterface $updater
    ) {
        $this->dataChangeFetcher = $dataChangeFetcher;
        $this->fetcher = $fetcher;
        $this->updater = $updater;
    }

    public function synchronizeIndex(DefinitionInterface $indexDefinition): array
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

    public function synchronizeDocumentsByIds(DefinitionInterface $indexDefinition, array $identifiers): array
    {
        $documentsData = $this->fetcher->fetchSourceData($indexDefinition, $identifiers);

        return $this->updater->synchronizeDocuments($indexDefinition, $documentsData);
    }
}
