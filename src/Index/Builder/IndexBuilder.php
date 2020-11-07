<?php

namespace Jeto\Synclastic\Index\Builder;

use Elasticsearch\Client as ElasticClient;
use Jeto\Synclastic\Index\Definition\IndexDefinitionInterface;
use Jeto\Synclastic\Index\Definition\IndexField;

final class IndexBuilder implements IndexBuilderInterface
{
    private ElasticClient $elastic;

    public function __construct(ElasticClient $elastic)
    {
        $this->elastic = $elastic;
    }

    public function buildIndex(IndexDefinitionInterface $indexDefinition): void
    {
        $indexName = $indexDefinition->getIndexName();

        $existingIndex = $this->fetchIndex($indexName);

        $indexFieldsProperties = $this->buildIndexFieldsProperties($indexDefinition->getIndexFields());

        if ($existingIndex !== null) {
            if ($this->indexRequiresReindexing($existingIndex, $indexFieldsProperties)) {
                $this->reindex($indexName, $indexFieldsProperties);
            } else {
                $this->updateIndexFields($indexName, $indexFieldsProperties);
            }
        } else {
            $newIndexName = $this->createIndex($indexName, $indexFieldsProperties);
            $this->elastic->indices()->putAlias(['index' => $newIndexName, 'name' => $indexName]);
        }
    }

    private function fetchIndex(string $indexName): ?array
    {
        // TODO: make sure this was necessary
        $indexExists = $this->elastic->indices()->exists(['index' => $indexName]);

        if (!$indexExists) {
            return null;
        }

        $indices = $this->elastic->indices()->get(['index' => $indexName]);

        return reset($indices);
    }

    private function createIndex(string $indexName, array $indexFieldsProperties, array $settings = []): string
    {
        $newIndexName = $indexName . '_' . time();

        $body = ['mappings' => ['properties' => $indexFieldsProperties]];
        if ($settings) {
            $body['settings'] = $settings;
        }

        $this->elastic->indices()->create([
            'index' => $newIndexName,
            'body' => $body,
        ]);

        return $newIndexName;
    }

    private function reindex(string $indexName, array $indexFieldsProperties): void
    {
        $indexSettings = $this->elastic->indices()->getSettings(['index' => $indexName]);
        $currentSettings = reset($indexSettings)['settings']['index'];

        $newIndexName = $this->createIndex($indexName, $indexFieldsProperties, [
            'number_of_shards' => $currentSettings['number_of_shards'],
            'number_of_replicas' => $currentSettings['number_of_replicas']
        ]);

        $this->elastic->reindex([
            'body' => [
                'source' => ['index' => $indexName],
                'dest' => ['index' => $newIndexName]
            ]
        ]);

        $aliases = $this->elastic->indices()->getAlias(['index' => $indexName]);

        foreach (array_keys($aliases) as $realIndexName) {
            $this->elastic->indices()->delete(['index' => $realIndexName]);
        }

        $this->elastic->indices()->putAlias(['index' => $newIndexName, 'name' => $indexName]);
    }

    private function updateIndexFields(string $indexName, array $indexFieldsProperties): void
    {
        $this->elastic->indices()->putMapping([
            'index' => $indexName,
            'body' => [
                'properties' => $indexFieldsProperties
            ]
        ]);
    }

    private function indexRequiresReindexing(array $index, array $indexFieldsProperties): bool
    {
        $indexMappingProperties = $index['mappings']['properties'];

        if (count($indexMappingProperties) !== count($indexFieldsProperties)) {
            return true;
        }

        foreach ($indexFieldsProperties as $fieldName => $indexFieldProperty) {
            if (!isset($indexMappingProperties[$fieldName])) {
                return true;
            }

            $indexFieldType = $indexMappingProperties[$fieldName]['type'];

            $typeIsCompatible = in_array($indexFieldType, [null, $indexFieldProperty['type']], true);

            if (!$typeIsCompatible) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param IndexField[] $indexFields
     */
    private function buildIndexFieldsProperties(array $indexFields): array
    {
        $properties = [];

        foreach ($indexFields as $indexField) {
            $fieldProperties = [
                'type' => $indexField->getType()
            ];

            $subFields = $indexField->getSubFields();
            if ($subFields) {
                $fieldProperties['properties'] = $this->buildIndexFieldsProperties($indexField->getSubFields());
            }

            $properties[$indexField->getName()] = $fieldProperties;
        }

        return $properties;
    }
}
