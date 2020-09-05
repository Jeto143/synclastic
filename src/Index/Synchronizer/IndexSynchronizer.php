<?php

namespace Jeto\Sqlastic\Index\Synchronizer;

use Elasticsearch\Client as ElasticClient;
use Jeto\Sqlastic\Database\ConnectionSettings;
use Jeto\Sqlastic\Database\DataChange;
use Jeto\Sqlastic\Database\DataConverter\DataConverterFactory;
use Jeto\Sqlastic\Database\DataConverter\DataConverterInterface;
use Jeto\Sqlastic\Database\Introspection\DatabaseInstrospectorFactory;
use Jeto\Sqlastic\Database\Introspection\DatabaseIntrospectorInterface;
use Jeto\Sqlastic\Database\PdoFactory;
use Jeto\Sqlastic\Mapping\FieldMapping\BasicFieldMappingInterface;
use Jeto\Sqlastic\Mapping\FieldMapping\ComputedFieldMappingInterface;
use Jeto\Sqlastic\Mapping\MappingInterface;

final class IndexSynchronizer implements IndexSynchronizerInterface
{
    private ElasticClient $elastic;
    private \PDO $pdo;
    private DatabaseIntrospectorInterface $databaseIntrospector;
    private DataConverterInterface $dataConverter;

    public function __construct(
        ElasticClient $elastic,
        ConnectionSettings $connectionSettings,
        DatabaseIntrospectorInterface $databaseIntrospector = null,
        DataConverterInterface $dataConverter = null
    ) {
        $this->elastic = $elastic;
        $this->pdo = (new PdoFactory())->create($connectionSettings);
        $this->databaseIntrospector = $databaseIntrospector
            ?? (new DatabaseInstrospectorFactory())->create($connectionSettings);
        $this->dataConverter = $dataConverter
            ?? (new DataConverterFactory())->create($connectionSettings->getDriverName());
    }

    public function synchronizeIndex(MappingInterface $mapping): void
    {
        $databaseName = $mapping->getDatabaseName();
        $indexName = $mapping->getIndexName();

        $processedDataChangesIds = [];
        $elasticOperations = [];

        foreach ($this->queryDataChanges($databaseName, $indexName) as $dataChange) {
            $docOperations = $this->computeDocumentSyncOperations(
                $databaseName,
                $dataChange,
                $mapping->getBasicFieldsMappings(),
                $mapping->getComputedFieldsMappings()
            );
            $elasticOperations = [...$elasticOperations, ...$docOperations];
            $processedDataChangesIds[] = $dataChange->getId();
        }

        if ($processedDataChangesIds) {
            $this->elastic->bulk(['body' => $elasticOperations]);
            $this->clearProcessedDataChanges($databaseName, $processedDataChangesIds);
        }
    }

    /**
     * @param BasicFieldMappingInterface[] $basicFieldsMappings
     * @param ComputedFieldMappingInterface[] $computedFieldsMappings
     * @return mixed[][]
     */
    private function computeDocumentSyncOperations(
        string $databaseName,
        DataChange $dataChange,
        array $basicFieldsMappings,
        array $computedFieldsMappings
    ): array {
        $tableName = $dataChange->getObjectType();
        $primaryKeyValue = $dataChange->getObjectId();
        $indexName = $dataChange->getIndexName();

        $rowData = $this->computeRowData(
            $databaseName,
            $tableName,
            $primaryKeyValue,
            $basicFieldsMappings,
            $computedFieldsMappings
        );

        $rowExists = $rowData !== null;
        $documentExists = $this->elastic->exists(['index' => $indexName, 'id' => $primaryKeyValue]);

        $operations = [];

        $operationIndexAndId = ['_index' => $indexName, '_id' => $primaryKeyValue];
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
     * @return iterable|DataChange[]
     */
    private function queryDataChanges(string $databaseName, string $indexName): iterable
    {
        $statement = $this->pdo->prepare(<<<SQL
            SELECT "id", "object_type" AS objectType, "object_id" AS objectId 
            FROM "{$databaseName}"."data_change" 
            WHERE "index" = ?
            ORDER BY "object_type", "object_id"
        SQL);

        $statement->setFetchMode(\PDO::FETCH_CLASS, DataChange::class);
        $statement->execute([$indexName]);

        return $statement;
    }

    /**
     * @param string[] $columnsNames
     * @return mixed[]|null
     */
    private function queryTableRowData(
        string $databaseName,
        string $tableName,
        int $primaryKeyValue,
        array $columnsNames
    ): ?array {
        $columnsNamesSql = implode(',', array_map(fn($columnName) => '"' . $columnName . '"', $columnsNames));

        $primaryKeyName = $this->databaseIntrospector->fetchPrimaryKeyName($databaseName, $tableName);

        $sql = sprintf(
            "SELECT {$columnsNamesSql} FROM \"%s\".\"%s\" WHERE \"%s\" = ?",
            $databaseName,
            $tableName,
            $primaryKeyName
        );

        $statement = $this->pdo->prepare($sql);
        $statement->execute([$primaryKeyValue]);

        return $statement->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * @param BasicFieldMappingInterface[] $basicFieldsMappings
     * @param ComputedFieldMappingInterface[] $computedFieldsMappings
     * @return mixed[]
     */
    private function computeRowData(
        string $databaseName,
        string $tableName,
        int $primaryKeyValue,
        array $basicFieldsMappings,
        array $computedFieldsMappings
    ): array {
        $columnsNames = $this->computeBasicMappedColumnsNames($basicFieldsMappings);
        $columnsTypes = $this->databaseIntrospector->fetchColumnsTypes($databaseName, $tableName);

        $rowData = $this->queryTableRowData($databaseName, $tableName, $primaryKeyValue, $columnsNames);

        foreach ($rowData as $columnName => &$value) {
            $value = $this->dataConverter->convertValue($columnsTypes[$columnName], $value);
        }
        unset($value);

        foreach ($computedFieldsMappings as $computedFieldMapping) {
            $computedValue = $this->queryComputedFieldValue($computedFieldMapping, $primaryKeyValue);

            $rowData[$computedFieldMapping->getIndexFieldName()] = $computedValue;
        }

        return $rowData;
    }

    private function queryComputedFieldValue(
        ComputedFieldMappingInterface $computedFieldMapping,
        int $primaryKeyValue
    ) {
        $statement = $this->pdo->prepare($computedFieldMapping->getValueQuery());
        $statement->execute([':id' => $primaryKeyValue]);

        return $statement->fetchColumn(0);
    }

    /**
     * @param BasicFieldMappingInterface[] $basicFieldsMappings
     * @return string[]
     */
    private function computeBasicMappedColumnsNames(array $basicFieldsMappings): array
    {
        return array_unique(array_map(
            fn(BasicFieldMappingInterface $basicFieldMapping): string => $basicFieldMapping->getDatabaseColumnName(),
            $basicFieldsMappings
        ));
    }

    /**
     * @param int[] $processedChangesIds
     */
    private function clearProcessedDataChanges(string $databaseName, array $processedChangesIds): void
    {
        $in = str_repeat('?,', count($processedChangesIds) - 1) . '?';
        $this->pdo
            ->prepare("DELETE FROM \"{$databaseName}\".\"data_change\" WHERE \"id\" IN ({$in})")
            ->execute($processedChangesIds);
    }
}
