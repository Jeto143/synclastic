<?php

namespace Jeto\Synclastic\Index\DataChange;

use Jeto\Synclastic\Index\Definition\DefinitionInterface;

interface DataChangeFetcherInterface
{
    /**
     * @return DataChange[]
     */
    public function fetchDataChanges(DefinitionInterface $indexDefinition): array;

    /**
     * @var DataChange[] $dataChanges
     */
    public function onDataChangesProcessed(array $dataChanges): void;
}
