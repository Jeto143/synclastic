<?php

namespace Jeto\Synclastic\Index\Updater;

use Elasticsearch\Client as ElasticClient;
use Jeto\Synclastic\Index\Definition\DefinitionInterface;
use Jeto\Synclastic\Index\Operation\Operation;

final class Updater implements UpdaterInterface
{
    private const DEFAULT_BATCH_SIZE = 100;
    private const ACTION_SYNCHRONIZE = 0;
    private const ACTION_ADD = 1;
    private const ACTION_UPDATE = 2;
    private const ACTION_DELETE = 3;

    private ElasticClient $elastic;

    private int $batchSize;

    public function __construct(
        ElasticClient $elastic,
        int $batchSize = self::DEFAULT_BATCH_SIZE
    ) {
        $this->elastic = $elastic;
        $this->batchSize = $batchSize;
    }

    public function synchronizeDocuments(DefinitionInterface $indexDefinition, iterable $documentsData): array
    {
        return $this->computeAndApplyOperations($indexDefinition, $documentsData, self::ACTION_SYNCHRONIZE);
    }

    public function addDocuments(DefinitionInterface $indexDefinition, iterable $documentsData): array
    {
        return $this->computeAndApplyOperations($indexDefinition, $documentsData, self::ACTION_ADD);
    }

    public function updateDocuments(DefinitionInterface $indexDefinition, iterable $documentsData): array
    {
        return $this->computeAndApplyOperations($indexDefinition, $documentsData, self::ACTION_UPDATE);
    }

    public function deleteDocuments(DefinitionInterface $indexDefinition, iterable $documentsData): array
    {
        return $this->computeAndApplyOperations($indexDefinition, $documentsData, self::ACTION_DELETE);
    }

    /**
     * @return Operation[]
     */
    private function computeAndApplyOperations(
        DefinitionInterface $indexDefinition,
        iterable $documentsData,
        int $action
    ): array {
        $indexOperations = [];
        $elasticOperations = [];

        foreach ($documentsData as $documentData) {
            $indexOperation = $this->computeIndexOperation($indexDefinition, $documentData, $action);
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
     * @param mixed[] $documentData
     */
    private function computeIndexOperation(
        DefinitionInterface $indexDefinition,
        array $documentData,
        int $action
    ): ?Operation {
        $indexName = $indexDefinition->getIndexName();
        $identifierValue = $documentData[$indexDefinition->getIdentifierFieldName()];

        switch ($action) {
            case self::ACTION_ADD:
                return new Operation($indexName, $identifierValue, Operation::TYPE_ADD, $documentData);

            case self::ACTION_UPDATE:
                return new Operation($indexName, $identifierValue, Operation::TYPE_UPDATE, $documentData);

            case self::ACTION_DELETE:
                return new Operation($indexName, $identifierValue, Operation::TYPE_DELETE);

            case self::ACTION_SYNCHRONIZE:
            default:
                $rowExists = $documentData !== null;
                $documentExists = $this->elastic->exists(['index' => $indexName, 'id' => $identifierValue]);

                if ($rowExists) {
                    return $documentExists
                        ? new Operation($indexName, $identifierValue, Operation::TYPE_UPDATE, $documentData)
                        : new Operation($indexName, $identifierValue, Operation::TYPE_ADD, $documentData);
                }

                if ($documentExists) {
                    return new Operation($indexName, $identifierValue, Operation::TYPE_DELETE);
                }
        }

        return null;
    }
}
