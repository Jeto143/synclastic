<?php

namespace Jeto\Sqlastic\Mapping\Database;

use Jeto\Sqlastic\Database\ConnectionSettings;
use Jeto\Sqlastic\Database\Introspector\DatabaseInstrospectorFactory;
use Jeto\Sqlastic\Database\Introspector\DatabaseIntrospectorInterface;
use Jeto\Sqlastic\Database\PdoFactory;
use Jeto\Sqlastic\Mapping\Database\FieldMapping\BasicFieldMapping;
use Jeto\Sqlastic\Mapping\Database\FieldMapping\BasicFieldMappingInterface;
use Jeto\Sqlastic\Mapping\IndexField;

class BasicDatabaseMapping implements DatabaseMappingInterface
{
    protected \PDO $pdo;
    protected string $databaseName;
    protected string $tableName;
    protected string $indexName;
    /** @var BasicFieldMappingInterface[] */
    protected array $basicFieldsMappings;
    protected DatabaseIntrospectorInterface $databaseIntrospector;

    public function __construct(
        ConnectionSettings $connectionSettings,
        string $databaseName,
        string $tableName,
        string $indexName,
        DatabaseIntrospectorInterface $databaseIntrospector = null
    ) {
        $this->databaseName = $databaseName;
        $this->tableName = $tableName;
        $this->indexName = $indexName;

        $this->pdo = (new PdoFactory())->create($connectionSettings);
        $this->databaseIntrospector = $databaseIntrospector
            ?? (new DatabaseInstrospectorFactory())->create($connectionSettings);

        $this->basicFieldsMappings = $this->computeBasicFieldsMappings();
    }

    public function getIndexName(): string
    {
        return $this->indexName;
    }

    public function getIndexFields(): array
    {
        return array_map(
            static fn(BasicFieldMappingInterface $basicFieldMapping)
                => new IndexField($basicFieldMapping->getIndexFieldName(), $basicFieldMapping->getIndexFieldType()),
            $this->basicFieldsMappings
        );
    }

    public function getIdentifierFieldName(): string
    {
        return $this->databaseIntrospector->fetchPrimaryKeyName($this->databaseName, $this->tableName);
    }

    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getBasicFieldsMappings(): array
    {
        return $this->basicFieldsMappings;
    }

    public function getComputedFieldsMappings(): array
    {
        return [];
    }

    public function fetchIndexData(): iterable
    {
        $sqlColumnsList = $this->computeSqlColumnsList();

        $sql = sprintf("SELECT {$sqlColumnsList} FROM \"%s\".\"%s\" LIMIT 200", $this->databaseName, $this->tableName);

        return $this->pdo->query($sql, \PDO::FETCH_ASSOC);
    }

    public function fetchDocumentData($identifier): ?array
    {
        $sqlColumnsList = $this->computeSqlColumnsList();

        $primaryKeyName = $this->databaseIntrospector->fetchPrimaryKeyName($this->databaseName, $this->tableName);

        $sql = sprintf(
            "SELECT {$sqlColumnsList} FROM \"%s\".\"%s\" WHERE \"%s\" = ?",
            $this->databaseName,
            $this->tableName,
            $primaryKeyName
        );

        $statement = $this->pdo->prepare($sql);
        $statement->execute([$identifier]);

        return $statement->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * @return BasicFieldMappingInterface[]
     */
    private function computeBasicFieldsMappings(): array
    {
        $columnsTypes = $this->databaseIntrospector->fetchColumnsTypes($this->databaseName, $this->tableName);

        $fieldsMappings = [];

        foreach ($columnsTypes as $columnName => $columnType) {
            $fieldsMappings[$columnName] = new BasicFieldMapping($columnName, $columnType);
        }

        return $fieldsMappings;
    }

    private function computeSqlColumnsList(): string
    {
        $enclosedColumnsNames = array_map(
            static fn(BasicFieldMappingInterface $basicFieldMapping)
                => '"' . $basicFieldMapping->getDatabaseColumnName() . '"',
            $this->basicFieldsMappings
        );

        return implode(',', $enclosedColumnsNames);
    }
}
