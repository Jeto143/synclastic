<?php

namespace Jeto\Synclastic\Database\DataFetcher;

use Jeto\Synclastic\Database\DatabaseConnectionSettings;
use Jeto\Synclastic\Database\DataConverter\DataConverterInterface;
use Jeto\Synclastic\Database\Introspector\DatabaseInstrospectorFactory;
use Jeto\Synclastic\Database\Introspector\DatabaseIntrospectorInterface;
use Jeto\Synclastic\Database\Mapping\BasicFieldMappingInterface;
use Jeto\Synclastic\Database\Mapping\ComputedFieldMappingInterface;
use Jeto\Synclastic\Database\Mapping\DatabaseMappingInterface;
use Jeto\Synclastic\Database\Mapping\NestedArrayFieldMappingInterface;
use Jeto\Synclastic\Database\PdoFactory;
use Jeto\Synclastic\Index\DataFetcher\DataFetcherInterface;
use Jeto\Synclastic\Index\Definition\IndexDefinitionInterface;

class BasicDataFetcher implements DataFetcherInterface
{
    protected \PDO $pdo;

    protected DatabaseMappingInterface $databaseMapping;

    protected ?DataConverterInterface $dataConverter;

    protected DatabaseIntrospectorInterface $databaseIntrospector;

    public function __construct(
        DatabaseMappingInterface $databaseMapping,
        DatabaseConnectionSettings $connectionSettings,
        ?DataConverterInterface $dataConverter = null,
        ?DatabaseIntrospectorInterface $databaseIntrospector = null
    ) {
        $this->databaseMapping = $databaseMapping;
        $this->pdo = (new PdoFactory())->create($connectionSettings);
        $this->dataConverter = $dataConverter;
        $this->databaseIntrospector = $databaseIntrospector
            ?? (new DatabaseInstrospectorFactory())->create($connectionSettings);
    }

    public function fetchSourceData(IndexDefinitionInterface $indexDefinition, ?array $identifiers = null): iterable
    {
        $primaryKeyName = $this->databaseIntrospector->fetchPrimaryKeyName(
            $this->databaseMapping->getDatabaseName(),
            $this->databaseMapping->getTableName()
        );

        $tableData = $this->fetchTableData($primaryKeyName, $identifiers);

        $computedFieldsMappings = $this->databaseMapping->getComputedFieldsMappings();
        $nestedArrayFieldsMappings = $this->databaseMapping->getNestedArrayFieldsMappings();

        if (!$computedFieldsMappings && !$nestedArrayFieldsMappings && $this->dataConverter === null) {
            yield from $tableData;
        } else {
            foreach ($tableData as $rowData) {
                $identifier = $rowData[$primaryKeyName];

                foreach ($computedFieldsMappings as $computedFieldMapping) {
                    $computedValue = $this->queryComputedFieldValue($computedFieldMapping, $identifier);
                    $rowData[$computedFieldMapping->getIndexFieldName()] = $computedValue;
                }

                foreach ($nestedArrayFieldsMappings as $nestedArrayFieldMapping) {
                    $computedValues = $this->queryNestedArrayFieldValues($nestedArrayFieldMapping, $identifier);
                    $rowData[$nestedArrayFieldMapping->getIndexFieldName()] = $computedValues;
                }

                if ($this->dataConverter !== null) {
                    $rowData = $this->convertRowData($rowData);
                }

                yield $rowData;
            }
        }
    }

    private function generateSqlColumnsList(): string
    {
        $enclosedColumnsNames = array_map(
            static fn(BasicFieldMappingInterface $basicFieldMapping)
                => '"' . $basicFieldMapping->getDatabaseColumnName() . '"',
            $this->databaseMapping->getBasicFieldsMappings()
        );

        return implode(',', $enclosedColumnsNames);
    }

    /**
     * @param mixed $primaryKeyValue
     * @return mixed
     */
    private function queryComputedFieldValue(ComputedFieldMappingInterface $computedFieldMapping, $primaryKeyValue)
    {
        $statement = $this->pdo->prepare($computedFieldMapping->getValueQuery());
        $statement->execute([':id' => $primaryKeyValue]);

        return $statement->fetchColumn(0);
    }

    /**
     * @param mixed $primaryKeyValue
     */
    private function queryNestedArrayFieldValues(
        NestedArrayFieldMappingInterface $nestedArrayFieldMapping,
        $primaryKeyValue
    ): array {
        $statement = $this->pdo->prepare($nestedArrayFieldMapping->getValuesQuery());
        $statement->execute([':id' => $primaryKeyValue]);

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function convertRowData(array $rowData): array
    {
        return array_map(function (string $columnName, $value): array {
            $columnType = $this->findColumnType($columnName);   // FIXME: check null?

            return $this->dataConverter->convertValue($columnType, $value);
        }, array_keys($rowData), $rowData);
    }

    private function findColumnType(string $columnName): ?string
    {
        foreach ($this->databaseMapping->getBasicFieldsMappings() as $basicFieldMapping) {
            if ($basicFieldMapping->getDatabaseColumnName() === $columnName) {
                return $basicFieldMapping->getDatabaseColumnType();
            }
        }

        return null;
    }

    private function fetchTableData(string $primaryKeyName, ?array $identifiers): iterable
    {
        $sqlColumnsList = $this->generateSqlColumnsList();

        if ($identifiers) {
            $in = str_repeat('?,', count($identifiers) - 1) . '?';
            $sql = sprintf(
                "SELECT {$sqlColumnsList} FROM \"%s\".\"%s\" WHERE \"%s\" IN ({$in})",
                $this->databaseMapping->getDatabaseName(),
                $this->databaseMapping->getTableName(),
                $primaryKeyName
            );

            $tableData = $this->pdo->prepare($sql);
            $tableData->setFetchMode(\PDO::FETCH_ASSOC);
            $tableData->execute($identifiers);
        } else {
            $sql = sprintf(
                "SELECT {$sqlColumnsList} FROM \"%s\".\"%s\" LIMIT 10",    // FIXME LIMIT
                $this->databaseMapping->getDatabaseName(),
                $this->databaseMapping->getTableName()
            );

            $tableData = $this->pdo->query($sql, \PDO::FETCH_ASSOC);
        }

        return $tableData;
    }
}
