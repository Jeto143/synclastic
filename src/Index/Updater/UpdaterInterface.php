<?php

namespace Jeto\Synclastic\Index\Updater;

use Jeto\Synclastic\Index\Definition\DefinitionInterface;
use Jeto\Synclastic\Index\Operation\Operation;

interface UpdaterInterface
{
    /**
     * @param iterable|mixed[][] $documentsData
     * @return Operation[]
     */
    public function synchronizeDocuments(DefinitionInterface $indexDefinition, iterable $documentsData): array;

    /**
     * @param iterable|mixed[][] $documentsData
     * @return Operation[]
     */
    public function addDocuments(DefinitionInterface $indexDefinition, iterable $documentsData): array;

    /**
     * @param iterable|mixed[][] $documentsData
     * @return Operation[]
     */
    public function updateDocuments(DefinitionInterface $indexDefinition, iterable $documentsData): array;

    /**
     * @param iterable|mixed[][] $documentsData
     * @return Operation[]
     */
    public function deleteDocuments(DefinitionInterface $indexDefinition, iterable $documentsData): array;
}
