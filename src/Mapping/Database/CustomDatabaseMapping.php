<?php

namespace Jeto\Sqlastic\Mapping\Database;

use Jeto\Sqlastic\Database\ConnectionSettings;
use Jeto\Sqlastic\Database\Introspector\DatabaseIntrospectorInterface;
use Jeto\Sqlastic\Mapping\Database\FieldMapping\ComputedFieldMappingInterface;

class CustomDatabaseMapping extends BasicDatabaseMapping
{
    /** @var ComputedFieldMappingInterface[] */
    protected array $computedFieldsMappings;

    public function __construct(
        ConnectionSettings $connectionSettings,
        string $databaseName,
        string $tableName,
        string $indexName,
        array $basicFieldsMappings = [],
        array $computedFieldsMappings = [],
        DatabaseIntrospectorInterface $databaseIntrospector = null
    ) {
        parent::__construct($connectionSettings, $databaseName, $tableName, $indexName, $databaseIntrospector);
        if ($basicFieldsMappings) {
            $this->basicFieldsMappings = $basicFieldsMappings;
        }
        $this->computedFieldsMappings = $computedFieldsMappings;
    }

    public function getComputedFieldsMappings(): array
    {
        return $this->computedFieldsMappings;
    }

    public function fetchIndexData(): iterable
    {
        $indexData = parent::fetchIndexData();

        if (!$this->computedFieldsMappings) {
            return $indexData;
        }

        $primaryKeyName = $this->databaseIntrospector->fetchPrimaryKeyName($this->databaseName, $this->tableName);

        foreach ($indexData as $rowData) {
            foreach ($this->computedFieldsMappings as $computedFieldMapping) {
                $rowData[$computedFieldMapping->getIndexFieldName()] = $this->queryComputedFieldValue(
                    $computedFieldMapping,
                    $rowData[$primaryKeyName]
                );
            }
            yield $rowData;
        }
    }

    public function fetchDocumentData($identifier): ?array
    {
        $rowData = parent::fetchDocumentData($identifier);

        if ($rowData !== null) {
            foreach ($this->computedFieldsMappings as $computedFieldMapping) {
                $computedValue = $this->queryComputedFieldValue($computedFieldMapping, $identifier);

                $rowData[$computedFieldMapping->getIndexFieldName()] = $computedValue;
            }
        }

        return $rowData;
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
}
