<?php

namespace Jeto\Elasticize\IndexBuilder;

use Elasticsearch\Client as ElasticClient;
use Jeto\Elasticize\DatabaseInstrospector\DatabaseInstrospectorFactory;
use Jeto\Elasticize\DatabaseInstrospector\DatabaseIntrospectorInterface;
use Jeto\Elasticize\FieldMapping\BasicFieldMappingInterface;
use Jeto\Elasticize\FieldMapping\FieldMappingInterface;
use Jeto\Elasticize\Mapping\MappingInterface;
use Jeto\Elasticize\MappingConfiguration\MappingConfigurationInterface;

final class IndexBuilder implements IndexBuilderInterface
{
    private ElasticClient $elastic;

    public function __construct(ElasticClient $elastic)
    {
        $this->elastic = $elastic;
    }

    public function buildIndex(MappingInterface $mapping): void
    {
        $indexName = $mapping->getIndexName();

        $existingIndex = $this->fetchIndex($indexName);
        $indexTargetFieldsTypes = $this->computeIndexTargetFieldsTypes($mapping);

        if ($existingIndex !== null) {
            if ($this->indexRequiresReindexing($existingIndex, $indexTargetFieldsTypes)) {
                $this->reindex($indexName, $indexTargetFieldsTypes);
            } else {
                $this->updateIndexFields($indexName, $indexTargetFieldsTypes);
            }
        } else {
            $this->createIndex($indexName, $indexTargetFieldsTypes);
        }
    }

    private function fetchIndex(string $indexName): ?array
    {
        $indexExists = $this->elastic->indices()->exists(['index' => $indexName]);

        if (!$indexExists) {
            return null;
        }

        $indices = $this->elastic->indices()->get(['index' => $indexName]);

        return reset($indices);
    }

    /**
     * @return string[]
     */
    private function computeIndexTargetFieldsTypes(MappingInterface $mapping): array
    {
        /** @var FieldMappingInterface[] $fieldsMappings */
        $fieldsMappings = array_merge($mapping->getBasicFieldsMappings(), $mapping->getComputedFieldsMappings());

        $indexFieldsTypes = [];

        foreach ($fieldsMappings as $basicFieldMapping) {
            $indexFieldsTypes[$basicFieldMapping->getIndexFieldName()] = [
                'type' => $basicFieldMapping->getIndexFieldType()
            ];
        }

        return $indexFieldsTypes;
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
        $this->elastic->indices()->putAlias([
          'index' => $newIndexName,
          'name' => $indexName
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

        $this->elastic->indices()->delete(['index' => $indexName]);
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
            $typeIsIncompatible = !in_array($indexFieldType, [null, $indexTargetFieldType['type']], true);
            if ($typeIsIncompatible) {
                return true;
            }
        }

        return false;
    }
}
