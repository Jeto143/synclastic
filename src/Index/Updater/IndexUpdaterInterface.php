<?php

namespace Jeto\Synclastic\Index\Updater;

use Jeto\Synclastic\Index\Definition\IndexDefinitionInterface;
use Jeto\Synclastic\Index\Operation\IndexOperation;

interface IndexUpdaterInterface
{
    /**
     * @param iterable|mixed[][] $sourceData
     * @return IndexOperation[]
     */
    public function synchronizeDocuments(IndexDefinitionInterface $indexDefinition, iterable $sourceData): array;

    /**
     * @param iterable|mixed[][] $sourceData
     * @return IndexOperation[]
     */
    public function addDocuments(IndexDefinitionInterface $indexDefinition, iterable $sourceData): array;

    /**
     * @param iterable|mixed[][] $sourceData
     * @return IndexOperation[]
     */
    public function updateDocuments(IndexDefinitionInterface $indexDefinition, iterable $sourceData): array;

    /**
     * @param iterable|mixed[][] $sourceData
     * @return IndexOperation[]
     */
    public function deleteDocuments(IndexDefinitionInterface $indexDefinition, iterable $sourceData): array;
}
