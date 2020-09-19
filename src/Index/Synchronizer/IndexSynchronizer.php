<?php

namespace Jeto\Sqlastic\Index\Synchronizer;

use Jeto\Sqlastic\Index\Updater\IndexUpdaterInterface;
use Jeto\Sqlastic\Mapping\DataChange;
use Jeto\Sqlastic\Mapping\DataChangeProviderInterface;
use Jeto\Sqlastic\Mapping\MappingInterface;

final class IndexSynchronizer implements IndexSynchronizerInterface
{
    private IndexUpdaterInterface $indexUpdater;
    private DataChangeProviderInterface $dataChangeProvider;

    public function __construct(IndexUpdaterInterface $indexUpdater, DataChangeProviderInterface $dataChangeProvider)
    {
        $this->indexUpdater = $indexUpdater;
        $this->dataChangeProvider = $dataChangeProvider;
    }

    public function synchronizeIndex(MappingInterface $mapping): void
    {
        $dataChanges = $this->dataChangeProvider->fetchDataChanges($mapping);
        $identifiers = array_map(static fn(DataChange $dataChange) => $dataChange->getObjectId(), $dataChanges);

        $this->indexUpdater->updateDocuments($mapping, $identifiers);

        foreach ($dataChanges as $dataChange) {
            $this->dataChangeProvider->markDataChangeAsProcessed($dataChange);
        }
    }
}
