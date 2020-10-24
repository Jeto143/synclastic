<?php

namespace Jeto\Synclastic\Index\DataChange;

use Jeto\Synclastic\Index\Definition\IndexDefinitionInterface;

interface DataChangeFetcherInterface
{
    /**
     * @return DataChange[]
     */
    public function fetchDataChanges(IndexDefinitionInterface $indexDefinition): array;

    /**
     * @var DataChange[] $dataChanges
     */
    public function onDataChangesProcessed(array $dataChanges): void;
}
