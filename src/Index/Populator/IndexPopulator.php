<?php

namespace Jeto\Sqlastic\Index\Populator;

use Elasticsearch\Client as ElasticClient;
use Jeto\Sqlastic\Database\DataConverter\DataConverterInterface;
use Jeto\Sqlastic\Mapping\IndexField;
use Jeto\Sqlastic\Mapping\MappingInterface;

final class IndexPopulator implements IndexPopulatorInterface
{
    private const POPULATE_BATCH_SIZE = 100;

    private ElasticClient $elastic;
    private ?DataConverterInterface $dataConverter;

    public function __construct(ElasticClient $elastic, ?DataConverterInterface $dataConverter = null)
    {
        $this->elastic = $elastic;
        $this->dataConverter = $dataConverter;
    }

    public function populateIndex(MappingInterface $mapping): void
    {
        $this->clearIndex($mapping->getIndexName());
        $this->batchPopulateIndex($mapping);
    }

    private function clearIndex(string $indexName): void
    {
        $this->elastic->deleteByQuery(
            [
                'index' => $indexName,
                'body' => [
                    'query' => [
                        'match_all' => (object)[]
                    ]
                ]
            ]
        );
    }

    private function batchPopulateIndex(MappingInterface $mapping): void
    {
        $params = ['body' => []];

        foreach ($mapping->fetchIndexData() as $i => $documentData) {
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

            $params['body'][] = [
                'index' => [
                    '_index' => $mapping->getIndexName(),
                    '_id' => $documentData[$mapping->getIdentifierFieldName()]
                ]
            ];

            $params['body'][] = $documentData;

            if ($i % self::POPULATE_BATCH_SIZE === 0) {
                $this->elastic->bulk($params);

                $params = ['body' => []];
            }
        }

        if ($params['body']) {
            $this->elastic->bulk($params);
        }
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
