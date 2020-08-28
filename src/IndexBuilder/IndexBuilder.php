<?php

namespace Jeto\Elasticize\IndexBuilder;

use Elasticsearch\Client as ElasticClient;
use Jeto\Elasticize\DatabaseInstrospector\DatabaseInstrospectorFactory;
use Jeto\Elasticize\DatabaseInstrospector\DatabaseIntrospectorInterface;

final class IndexBuilder implements IndexBuilderInterface
{
    private ElasticClient $elastic;
    private DatabaseIntrospectorInterface $databaseIntrospector;

    public function __construct(
        ElasticClient $elastic,
        \PDO $pdo,
        DatabaseIntrospectorInterface $databaseIntrospector = null
    ) {
        $this->elastic = $elastic;
        $this->databaseIntrospector = $databaseIntrospector ?? (new DatabaseInstrospectorFactory())->create($pdo);
    }

    public function buildIndex(string $databaseName, string $tableName): void
    {
        $indexTargetFieldsTypes = $this->computeIndexTargetFieldsTypes($databaseName, $tableName);

        $existingIndex = $this->fetchIndex($tableName);

        if ($existingIndex !== null) {
            if ($this->indexRequiresReindexing($existingIndex, $indexTargetFieldsTypes)) {
                $this->reindex($tableName, $indexTargetFieldsTypes);
            } else {
                $this->updateIndexFields($tableName, $indexTargetFieldsTypes);
            }
        } else {
            $this->createIndex($tableName, $indexTargetFieldsTypes);
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

    private function computeIndexTargetFieldsTypes(string $databaseName, string $tableName): array
    {
        $databaseFieldsTypes = $this->databaseIntrospector->fetchFieldsTypes($databaseName, $tableName);

        $indexFieldsTypes = [];

        foreach ($databaseFieldsTypes as $fieldName => $type) {
            $indexFieldsTypes[$fieldName] = [
              'type' => $this->getElasticType($type)
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

    private function getElasticType(string $sqlType): string
    {
        switch ($sqlType) {
            case 'int':
            case 'tinyint':
                return 'integer';
            case 'varchar':
            default:
                return 'text';
        }
    }
}
