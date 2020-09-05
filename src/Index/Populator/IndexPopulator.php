<?php


namespace Jeto\Sqlastic\Index\Populator;


use Elasticsearch\Client as ElasticClient;
use Jeto\Sqlastic\Database\ConnectionSettings;
use Jeto\Sqlastic\Database\DataConverter\DataConverterFactory;
use Jeto\Sqlastic\Database\DataConverter\DataConverterInterface;
use Jeto\Sqlastic\Database\Introspection\DatabaseInstrospectorFactory;
use Jeto\Sqlastic\Database\Introspection\DatabaseIntrospectorInterface;
use Jeto\Sqlastic\Database\PdoFactory;
use Jeto\Sqlastic\Mapping\FieldMapping\BasicFieldMappingInterface;
use Jeto\Sqlastic\Mapping\FieldMapping\ComputedFieldMappingInterface;
use Jeto\Sqlastic\Mapping\MappingInterface;

final class IndexPopulator implements IndexPopulatorInterface
{
    private const POPULATE_BATCH_SIZE = 100;

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

    public function populateIndex(MappingInterface $mapping): void
    {
        $databaseName = $mapping->getDatabaseName();
        $tableName = $mapping->getTableName();
        $indexName = $mapping->getIndexName();

        $columnsNames = $this->computeColumnsNames($mapping->getBasicFieldsMappings());

        $rowsData = $this->fetchTableRowsData($databaseName, $tableName, $columnsNames);

        $this->clearIndex($databaseName, $tableName);
        $this->batchPopulateIndex(
            $databaseName,
            $tableName,
            $indexName,
            $rowsData,
            $mapping->getComputedFieldsMappings()
        );
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

    /**
     * @param string[] $columnsNames
     * @return iterable|mixed[]
     */
    private function fetchTableRowsData(string $databaseName, string $tableName, array $columnsNames): iterable
    {
        $columnsNamesSql = implode(',', array_map(fn($columnName) => '"' . $columnName . '"', $columnsNames));

        $sql = sprintf("SELECT {$columnsNamesSql} FROM \"%s\".\"%s\" LIMIT 200", $databaseName, $tableName);

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
        string $indexName,
        iterable $rowsData,
        array $computedFieldsMappings
    ): void {
        $primaryKeyName = $this->databaseIntrospector->fetchPrimaryKeyName($databaseName, $tableName);
        $columnsTypes = $this->databaseIntrospector->fetchColumnsTypes($databaseName, $tableName);

        $params = ['body' => []];

        foreach ($rowsData as $rowIndex => $rowData) {
            foreach ($rowData as $columnName => &$value) {
                $value = $this->dataConverter->convertValue($columnsTypes[$columnName], $value);
            }
            unset($value);

            foreach ($computedFieldsMappings as $computedFieldMapping) {
                $rowData[$computedFieldMapping->getIndexFieldName()] = $this->queryComputedFieldValue(
                    $computedFieldMapping,
                    $rowData[$primaryKeyName]
                );
            }

            $params['body'][] = [
                'index' => [
                    '_index' => $indexName,
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
     * @param mixed $primaryKeyValue
     * @return mixed
     */
    private function queryComputedFieldValue(
        ComputedFieldMappingInterface $computedFieldMapping,
        $primaryKeyValue
    ): array {
        $statement = $this->pdo->prepare($computedFieldMapping->getValueQuery());
        $statement->execute([':id' => $primaryKeyValue]);

        return $statement->fetchColumn(0);
    }
}
