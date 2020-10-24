<?php

namespace Jeto\Synclastic\Index\Updater;

use Elasticsearch\Client as ElasticClient;
use Jeto\Synclastic\Index\Definition\IndexDefinitionInterface;
use Jeto\Synclastic\Index\Operation\IndexOperation;

final class IndexUpdater implements IndexUpdaterInterface
{
    private const DEFAULT_BATCH_SIZE = 100;
    private const ACTION_SYNCHRONIZE = 0;
    private const ACTION_ADD = 1;
    private const ACTION_UPDATE = 2;
    private const ACTION_DELETE = 3;

    private ElasticClient $elastic;

    private int $batchSize;

    public function __construct(ElasticClient $elastic, int $batchSize = self::DEFAULT_BATCH_SIZE)
    {
        $this->elastic = $elastic;
        $this->batchSize = $batchSize;
    }

    public function synchronizeDocuments(IndexDefinitionInterface $indexDefinition, iterable $sourceData): array
    {
        return $this->computeAndApplyOperations($indexDefinition, $sourceData, self::ACTION_SYNCHRONIZE);
    }

    public function addDocuments(IndexDefinitionInterface $indexDefinition, iterable $sourceData): array
    {
        return $this->computeAndApplyOperations($indexDefinition, $sourceData, self::ACTION_ADD);
    }

    public function updateDocuments(IndexDefinitionInterface $indexDefinition, iterable $sourceData): array
    {
        return $this->computeAndApplyOperations($indexDefinition, $sourceData, self::ACTION_UPDATE);
    }

    public function deleteDocuments(IndexDefinitionInterface $indexDefinition, iterable $sourceData): array
    {
        return $this->computeAndApplyOperations($indexDefinition, $sourceData, self::ACTION_DELETE);
    }

    /**
     * @return IndexOperation[]
     */
    private function computeAndApplyOperations(
        IndexDefinitionInterface $indexDefinition,
        iterable $sourceData,
        int $action
    ): array {
        $indexOperations = [];
        $elasticOperations = [];

        foreach ($sourceData as $sourceEntryData) {
            $indexOperation = $this->computeIndexOperation($indexDefinition, $sourceEntryData, $action);
            if ($indexOperation === null) {
                continue;
            }

            $indexOperations[] = $indexOperation;
            $elasticOperations = [...$elasticOperations, ...$indexOperation->toElasticOperations()];

            if ($this->batchSize > 0 && count($elasticOperations) % $this->batchSize === 0) {
                $this->elastic->bulk(['body' => $elasticOperations]);
                $elasticOperations = [];
            }
        }

        if ($elasticOperations) {
            $this->elastic->bulk(['body' => $elasticOperations]);
        }

        return $indexOperations;
    }

    /**
     * @param mixed[] $sourceEntryData
     */
    private function computeIndexOperation(
        IndexDefinitionInterface $indexDefinition,
        array $sourceEntryData,
        int $action
    ): ?IndexOperation {
        $indexName = $indexDefinition->getIndexName();
        $identifierValue = $sourceEntryData[$indexDefinition->getIdentifierFieldName()];

        switch ($action) {
            case self::ACTION_ADD:
                return new IndexOperation($indexName, $identifierValue, IndexOperation::TYPE_ADD, $sourceEntryData);

            case self::ACTION_UPDATE:
                return new IndexOperation($indexName, $identifierValue, IndexOperation::TYPE_UPDATE, $sourceEntryData);

            case self::ACTION_DELETE:
                return new IndexOperation($indexName, $identifierValue, IndexOperation::TYPE_DELETE);

            case self::ACTION_SYNCHRONIZE:
            default:
                $sourceEntryDataExists = $sourceEntryData !== null;
                $documentExists = $this->elastic->exists(['index' => $indexName, 'id' => $identifierValue]);

                if ($sourceEntryDataExists) {
                    return $documentExists
                        ? new IndexOperation($indexName, $identifierValue, IndexOperation::TYPE_UPDATE, $sourceEntryData)
                        : new IndexOperation($indexName, $identifierValue, IndexOperation::TYPE_ADD, $sourceEntryData);
                }

                if ($documentExists) {
                    return new IndexOperation($indexName, $identifierValue, IndexOperation::TYPE_DELETE);
                }
        }

        return null;
    }
}
