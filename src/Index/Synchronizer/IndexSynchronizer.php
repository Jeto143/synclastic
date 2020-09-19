<?php

namespace Jeto\Sqlastic\Index\Synchronizer;

use Elasticsearch\Client as ElasticClient;
use Jeto\Sqlastic\Database\DataConverter\DataConverterInterface;
use Jeto\Sqlastic\Mapping\DataChange;
use Jeto\Sqlastic\Mapping\DataChangeProviderInterface;
use Jeto\Sqlastic\Mapping\IndexField;
use Jeto\Sqlastic\Mapping\MappingInterface;

final class IndexSynchronizer implements IndexSynchronizerInterface
{
    private ElasticClient $elastic;
    private DataChangeProviderInterface $dataChangeManager;
    private ?DataConverterInterface $dataConverter;

    public function __construct(
        ElasticClient $elastic,
        DataChangeProviderInterface $dataChangeManager,
        DataConverterInterface $dataConverter = null
    ) {
        $this->elastic = $elastic;
        $this->dataChangeManager = $dataChangeManager;
        $this->dataConverter = $dataConverter;
    }

    public function synchronizeIndex(MappingInterface $mapping): void
    {
        $processedDataChanges = [];
        $elasticOperations = [];

        foreach ($this->dataChangeManager->fetchDataChanges($mapping) as $dataChange) {
            $docOperations = $this->computeDocumentSyncOperations($mapping, $dataChange);
            $elasticOperations = [...$elasticOperations, ...$docOperations];

            $processedDataChanges[] = $dataChange;
        }

        if ($processedDataChanges) {
            $this->elastic->bulk(['body' => $elasticOperations]);

            foreach ($processedDataChanges as $dataChange) {
                $this->dataChangeManager->markDataChangeAsProcessed($dataChange);
            }
        }
    }

    /**
     * @return mixed[][]
     */
    private function computeDocumentSyncOperations(MappingInterface $mapping, DataChange $dataChange): array
    {
        $indexName = $mapping->getIndexName();
        $documentData = $mapping->fetchDocumentData($dataChange->getObjectId());
        $primaryKeyValue = $documentData[$mapping->getIdentifierFieldName()];

        if ($this->dataConverter !== null) {
            foreach ($documentData as $columnName => &$value) {
                $field = $this->findField($mapping->getIndexFields(), $columnName);
                if ($field === null) {
                    throw new \InvalidArgumentException("TODO");
                }
                $value = $this->dataConverter->convertValue($field->getType(), $value);
            }
            unset($value);
        }

        $rowExists = $documentData !== null;
        $documentExists = $this->elastic->exists(['index' => $indexName, 'id' => $primaryKeyValue]);

        $operations = [];

        $operationIndexAndId = ['_index' => $indexName, '_id' => $primaryKeyValue];
        if ($rowExists) {
            if ($documentExists) {
                $operations[] = ['update' => $operationIndexAndId];
                $operations[] = ['doc' => $documentData];
            } else {
                $operations[] = ['index' => $operationIndexAndId];
                $operations[] = $documentData;
            }
        } elseif ($documentExists) {
            $operations[] = ['delete' => $operationIndexAndId];
        }

        return $operations;
    }

    /**
     * @param IndexField[] $fields
     */
    private function findField(array $fields, string $columnName): ?IndexField
    {
        foreach ($fields as $field) {
            if ($field->getName() === $columnName) {
                return $field;
            }
        }
        return null;
    }
}
