<?php

namespace Jeto\Synclastic\Index\Synchronizer;

use Jeto\Synclastic\Index\Definition\DefinitionInterface;
use Jeto\Synclastic\Index\Operation\Operation;

interface SynchronizerInterface
{
    /**
     * @return Operation[]
     */
    public function synchronizeIndex(DefinitionInterface $indexDefinition): array;

    /**
     * @param mixed[] $identifiers
     * @return Operation[]
     */
    public function synchronizeDocumentsByIds(DefinitionInterface $indexDefinition, array $identifiers): array;
}
