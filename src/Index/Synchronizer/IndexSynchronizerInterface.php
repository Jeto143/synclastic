<?php

namespace Jeto\Synclastic\Index\Synchronizer;

use Jeto\Synclastic\Index\Definition\IndexDefinitionInterface;
use Jeto\Synclastic\Index\Operation\IndexOperation;

interface IndexSynchronizerInterface
{
    /**
     * @return IndexOperation[]
     */
    public function synchronizeDocuments(IndexDefinitionInterface $indexDefinition): array;

    /**
     * @param mixed[] $identifiers
     * @return IndexOperation[]
     */
    public function synchronizeDocumentsByIds(IndexDefinitionInterface $indexDefinition, array $identifiers): array;
}
