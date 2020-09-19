<?php

namespace Jeto\Sqlastic\Index\Updater;

use Elasticsearch\Client as ElasticClient;
use Jeto\Sqlastic\Mapping\DataConverterInterface;
use Jeto\Sqlastic\Mapping\IndexField;
use Jeto\Sqlastic\Mapping\MappingInterface;

final class IndexUpdater implements IndexUpdaterInterface
{
    private const DEFAULT_BATCH_SIZE = 100;

    private ElasticClient $elastic;
    private ?DataConverterInterface $dataConverter;
    private int $batchSize;

    public function __construct(
        ElasticClient $elastic,
        ?DataConverterInterface $dataConverter = null,
        int $batchSize = self::DEFAULT_BATCH_SIZE
    ) {
        $this->elastic = $elastic;
        $this->dataConverter = $dataConverter;
        $this->batchSize = $batchSize;
    }

    public function updateDocuments(MappingInterface $mapping, array $identifiers): void
    {
        $elasticOperations = [];

        foreach ($identifiers as $identifier) {
            $docOperations = $this->computeDocumentSyncOperations($mapping, $identifier);
            $elasticOperations = [...$elasticOperations, ...$docOperations];

            if ($this->batchSize > 0 && count($elasticOperations) % $this->batchSize === 0) {
                $this->elastic->bulk(['body' => $elasticOperations]);
                $elasticOperations = [];
            }
        }

        if ($elasticOperations) {
            $this->elastic->bulk(['body' => $elasticOperations]);
        }
    }

    /**
     * @param mixed $identifier
     * @return mixed[][]
     */
    private function computeDocumentSyncOperations(MappingInterface $mapping, $identifier): array
    {
        $indexName = $mapping->getIndexName();
        $documentData = $mapping->fetchDocumentData($identifier);
        $primaryKeyValue = $documentData[$mapping->getIdentifierFieldName()];

        if ($this->dataConverter !== null) {
            $value = $this->convertDocumentData($mapping, $documentData);
            unset($value);
        }

        return $this->computeElasticOperations($indexName, $documentData, $primaryKeyValue);
    }

    private function convertDocumentData(MappingInterface $mapping, array $documentData): array
    {
        return array_map(function (string $fieldName, $fieldValue) use ($mapping): array {
            $field = $this->findField($mapping->getIndexFields(), $fieldName);

            if ($field === null) {
                throw new \InvalidArgumentException("TODO");    // TODO
            }

            return $this->dataConverter->convertValue($field->getSourceType(), $fieldValue);
        }, array_keys($documentData), $documentData);
    }

    private function computeElasticOperations(string $indexName, ?array $documentData, $primaryKeyValue): array
    {
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
    private function findField(array $fields, string $fieldName): ?IndexField
    {
        foreach ($fields as $field) {
            if ($field->getName() === $fieldName) {
                return $field;
            }
        }
        return null;
    }
}
