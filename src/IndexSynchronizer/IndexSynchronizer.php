<?php

namespace Jeto\Elasticize\IndexSynchronizer;

use Elasticsearch\Client as ElasticClient;
use Jeto\Elasticize\DatabaseInstrospector\DatabaseInstrospectorFactory;
use Jeto\Elasticize\DatabaseInstrospector\DatabaseIntrospectorInterface;
use Jeto\Elasticize\FieldMapping\BasicFieldMappingInterface;
use Jeto\Elasticize\FieldMapping\ComputedFieldMappingInterface;
use Jeto\Elasticize\Mapping\MappingInterface;

final class IndexSynchronizer implements IndexSynchronizerInterface
{
    private const POPULATE_BATCH_SIZE = 100;

    private ElasticClient $elastic;
    private \PDO $pdo;
    private DatabaseIntrospectorInterface $databaseIntrospector;

    public function __construct(
        ElasticClient $elastic,
        \PDO $pdo,
        DatabaseIntrospectorInterface $databaseIntrospector = null
    ) {
        $this->elastic = $elastic;
        $this->pdo = $pdo;
        $this->databaseIntrospector = $databaseIntrospector ?? (new DatabaseInstrospectorFactory())->create($pdo);
    }

    public function synchronizeIndex(MappingInterface $mapping): void
    {
        $databaseName = $mapping->getDatabaseName();
        $indexName = $mapping->getIndexName();

        $dataChanges = $this->queryDataChanges($databaseName, $indexName);

        $processedChangesIds = [];
        $elasticOperations = [];

        foreach ($dataChanges as $dataChange) {
            $docOperations = $this->computeDocumentSyncOperations(
                $databaseName,
                $dataChange->getObjectType(),
                $dataChange->getObjectId(),
                $mapping->getBasicFieldsMappings(),
                $mapping->getComputedFieldsMappings()
            );
            $elasticOperations = [...$elasticOperations, ...$docOperations];
            $processedChangesIds[] = $dataChange->getId();
        }

        if ($processedChangesIds) {
            $this->elastic->bulk(['body' => $elasticOperations]);

            $in = str_repeat('?,', count($processedChangesIds) - 1) . '?';
            $this->pdo->prepare("DELETE FROM data_change WHERE id IN ({$in})")->execute($processedChangesIds);
        }
    }

    public function clearAndSynchronizeIndex(MappingInterface $mapping): void
    {
        $databaseName = $mapping->getDatabaseName();
        $tableName = $mapping->getTableName();

        $columnsNames = $this->computeColumnsNames($mapping->getBasicFieldsMappings());

        $rowsData = $this->fetchTableRowsData($databaseName, $tableName, $columnsNames);

        $this->clearIndex($databaseName, $tableName);
        $this->batchPopulateIndex($databaseName, $tableName, $rowsData, $mapping->getComputedFieldsMappings());
    }

    /**
     * @param BasicFieldMappingInterface[] $basicFieldsMappings
     * @param ComputedFieldMappingInterface[] $computedFieldsMappings
     * @return mixed[][]
     */
    private function computeDocumentSyncOperations(
        string $databaseName,
        string $objectType,
        int $objectId,
        array $basicFieldsMappings,
        array $computedFieldsMappings
    ): array {
        $rowData = $this->computeRowData(
            $databaseName,
            $objectType,
            $objectId,
            $basicFieldsMappings,
            $computedFieldsMappings
        );

        $rowExists = $rowData !== null;
        $documentExists = $this->elastic->exists(['index' => $objectType, 'id' => $objectId]);

        $operationIndexAndId = ['_index' => $objectType, '_id' => $objectId];

        $operations = [];

        if ($rowExists) {
            if ($documentExists) {
                $operations[] = ['update' => $operationIndexAndId];
                $operations[] = ['doc' => $rowData];
            } else {
                $operations[] = ['index' => $operationIndexAndId];
                $operations[] = $rowData;
            }
        } elseif ($documentExists) {
            $operations[] = ['delete' => $operationIndexAndId];
        }

        return $operations;
    }

    /**
     * @return iterable|DatabaseDataChange[]
     */
    private function queryDataChanges(string $databaseName, string $indexName): iterable
    {
        $this->pdo->exec("USE {$databaseName}");

        $statement = $this->pdo->prepare(
            '
            SELECT id, object_type AS objectType, object_id AS objectId 
            FROM data_change 
            WHERE `index` = ?
            ORDER BY object_type, object_id
        '
        );

        $statement->setFetchMode(\PDO::FETCH_CLASS, DatabaseDataChange::class);
        $statement->execute([$indexName]);

        return $statement;
    }

    /**
     * @param string[] $columnsNames
     * @return mixed[]|null
     */
    private function fetchTableRowData(string $databaseName, string $tableName, int $rowId, array $columnsNames): ?array
    {
        $columnsNamesSql = implode(',', $columnsNames); // FIXME: protect columns (cross-DBMS)
        $primaryKeyName = $this->databaseIntrospector->fetchPrimaryKeyName($databaseName, $tableName);

        $this->pdo->exec("USE {$databaseName}");

        $sql = sprintf("SELECT {$columnsNamesSql} FROM %s WHERE %s = ?", $tableName, $primaryKeyName);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$rowId]);

        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * @param string[] $columnsNames
     * @return iterable|mixed[]
     */
    private function fetchTableRowsData(string $databaseName, string $tableName, array $columnsNames): iterable
    {
        $columnsNamesSql = implode(',', $columnsNames); // FIXME: protect columns (cross-DBMS)

        $sql = sprintf("SELECT {$columnsNamesSql} FROM %s LIMIT 200", $tableName);

        $this->pdo->exec("USE {$databaseName}");

        return $this->pdo->query($sql, \PDO::FETCH_ASSOC);
    }

    private function clearIndex(string $databaseName, string $indexName): void
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

    /**
     * @param ComputedFieldMappingInterface[] $computedFieldsMappings
     */
    private function batchPopulateIndex(
        string $databaseName,
        string $tableName,
        iterable $rowsData,
        array $computedFieldsMappings
    ): void {
        $primaryKeyName = $this->databaseIntrospector->fetchPrimaryKeyName($databaseName, $tableName);

        $params = ['body' => []];

        foreach ($rowsData as $rowIndex => $rowData) {
            foreach ($computedFieldsMappings as $computedFieldMapping) {
                $statement = $this->pdo->prepare($computedFieldMapping->getValueQuery());
                $statement->execute([':id' => $rowData[$primaryKeyName]]);
                $rowData[$computedFieldMapping->getIndexFieldName()] = $statement->fetchColumn(0);
            }

            $params['body'][] = [
                'index' => [
                    '_index' => $tableName,
                    '_id' => $rowData[$primaryKeyName]
                ]
            ];

            $params['body'][] = $rowData;

            if ($rowIndex % self::POPULATE_BATCH_SIZE === 0) {
                $this->elastic->bulk($params);

                $params = ['body' => []];
            }
        }

        if ($params['body']) {
            $this->elastic->bulk($params);
        }
    }

    /**
     * @param BasicFieldMappingInterface[] $basicFieldsMappings
     * @param ComputedFieldMappingInterface[] $computedFieldsMappings
     * @return mixed[]
     */
    private function computeRowData(
        string $databaseName,
        string $objectType,
        int $objectId,
        array $basicFieldsMappings,
        array $computedFieldsMappings
    ): array {
        $columnsNames = $this->computeColumnsNames($basicFieldsMappings);

        $rowData = $this->fetchTableRowData($databaseName, $objectType, $objectId, $columnsNames);

        foreach ($computedFieldsMappings as $computedFieldMapping) {
            $statement = $this->pdo->prepare($computedFieldMapping->getValueQuery());
            $statement->execute([':id' => $objectId]);

            $computedValue = $statement->fetchColumn(0);

            $rowData[$computedFieldMapping->getIndexFieldName()] = $computedValue;
        }

        return $rowData;
    }

    /**
     * @param BasicFieldMappingInterface[] $basicFieldsMappings
     * @return string[]
     */
    private function computeColumnsNames(array $basicFieldsMappings): array
    {
        return array_map(
            fn(BasicFieldMappingInterface $basicFieldMapping): string => $basicFieldMapping->getDatabaseColumnName(),
            $basicFieldsMappings
        );
    }
}
