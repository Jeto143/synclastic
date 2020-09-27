<?php

namespace Jeto\Synclastic\Index\Builder;

use Elasticsearch\Client as ElasticClient;
use Jeto\Synclastic\Index\Definition\DefinitionInterface;
use Jeto\Synclastic\Index\Definition\Field;

final class Builder implements BuilderInterface
{
    private ElasticClient $elastic;

    public function __construct(ElasticClient $elastic)
    {
        $this->elastic = $elastic;
    }

    public function buildIndex(DefinitionInterface $indexDefinition): void
    {
        $indexName = $indexDefinition->getIndexName();

        $existingIndex = $this->fetchIndex($indexName);

        $indexTargetFieldsTypes = array_map(
            static fn(Field $field): array => ['type' => $field->getType()],
            $indexDefinition->getIndexFields()
        );

        if ($existingIndex !== null) {
            if ($this->indexRequiresReindexing($existingIndex, $indexTargetFieldsTypes)) {
                $this->reindex($indexName, $indexTargetFieldsTypes);
            } else {
                $this->updateIndexFields($indexName, $indexTargetFieldsTypes);
            }
        } else {
            $newIndexName = $this->createIndex($indexName, $indexTargetFieldsTypes);
            $this->elastic->indices()->putAlias(['index' => $newIndexName, 'name' => $indexName]);
        }
    }

    private function fetchIndex(string $indexName): ?array
    {
        $indexExists = $this->elastic->indices()->exists(['index' => $indexName]);

        if (!$indexExists) {
            return null;
        }

        $indices = $this->elastic->indices()->get(['index' => $indexName]);

        return reset($indices) ?: null;
    }

    private function createIndex(string $indexName, array $fieldsDefinitions, array $settings = []): string
    {
        $newIndexName = $indexName . '_' . time();

        $body = ['mappings' => ['properties' => $fieldsDefinitions]];
        if ($settings) {
            $body['settings'] = $settings;
        }

        $this->elastic->indices()->create([
            'index' => $newIndexName,
            'body' => $body,
        ]);

        return $newIndexName;
    }

    private function reindex(string $indexName, array $fieldsDefinitions): void
    {
        $indexSettings = $this->elastic->indices()->getSettings(['index' => $indexName]);
        $currentSettings = $indexSettings[$indexName]['settings']['index'];

        $newIndexName = $this->createIndex($indexName, $fieldsDefinitions, [
            'number_of_shards' => $currentSettings['number_of_shards'],
            'number_of_replicas' => $currentSettings['number_of_replicas']
        ]);

        $this->elastic->reindex([
            'body' => [
                'source' => ['index' => $indexName],
                'dest' => ['index' => $newIndexName]
            ]
        ]);

        $aliases = $this->elastic->indices()->getAlias(['name' => $indexName]);

        foreach (array_keys($aliases) as $realIndexName) {
            $this->elastic->indices()->delete(['index' => $realIndexName]);
        }

        $this->elastic->indices()->putAlias(['index' => $newIndexName, 'name' => $indexName]);
    }

    private function updateIndexFields(string $indexName, array $fieldsDefinitions): void
    {
        $this->elastic->indices()->putMapping([
            'index' => $indexName,
            'body' => [
                'properties' => $fieldsDefinitions
            ]
        ]);
    }

    private function indexRequiresReindexing(array $index, array $indexTargetFieldsTypes): bool
    {
        foreach ($indexTargetFieldsTypes as $fieldName => $indexTargetFieldType) {
            $indexFieldType = $index['mappings']['properties'][$fieldName]['type'];

            $typeIsCompatible = in_array($indexFieldType, [null, $indexTargetFieldType['type']], true);

            if (!$typeIsCompatible) {
                return true;
            }
        }

        return false;
    }
}
