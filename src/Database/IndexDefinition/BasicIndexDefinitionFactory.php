<?php

namespace Jeto\Synclastic\Database\IndexDefinition;

use Jeto\Synclastic\Database\Introspector\DatabaseIntrospectorInterface;
use Jeto\Synclastic\Database\Mapping\DatabaseMappingInterface;
use Jeto\Synclastic\Database\Mapping\FieldMappingInterface;
use Jeto\Synclastic\Database\Mapping\NestedArrayFieldMappingInterface;
use Jeto\Synclastic\Index\Definition\IndexDefinition;
use Jeto\Synclastic\Index\Definition\IndexDefinitionInterface;
use Jeto\Synclastic\Index\Definition\IndexField;

final class BasicIndexDefinitionFactory
{
    private DatabaseIntrospectorInterface $databaseIntrospector;

    public function __construct(DatabaseIntrospectorInterface $databaseIntrospector)
    {
        $this->databaseIntrospector = $databaseIntrospector;
    }

    public function create(string $indexName, DatabaseMappingInterface $mapping): IndexDefinitionInterface
    {
        $identifierFieldName = $this->computeIdentifierFieldName($mapping->getDatabaseName(), $mapping->getTableName());
        $indexFields = $this->computeIndexFields($mapping);

        return new IndexDefinition($indexName, $indexFields, $identifierFieldName);
    }

    /**
     * @param DatabaseMappingInterface $mapping
     * @return IndexField[]
     */
    public function computeIndexFields(DatabaseMappingInterface $mapping): array
    {
        return array_merge(
            $this->computeIndexFieldsFromFieldsMappings(
                array_merge(
                    $mapping->getBasicFieldsMappings(),
                    $mapping->getComputedFieldsMappings()
                )
            ),
            array_map(
                fn(NestedArrayFieldMappingInterface $fieldMapping) =>
                    new IndexField(
                        $fieldMapping->getIndexFieldName(),
                        $fieldMapping->getIndexFieldType(),
                        $this->computeIndexFieldsFromFieldsMappings($fieldMapping->getBasicFieldsMappings())
                    ),
                $mapping->getNestedArrayFieldsMappings()
            )
        );
    }

    private function computeIdentifierFieldName(string $databaseName, string $tableName): string
    {
        return $this->databaseIntrospector->fetchPrimaryKeyName($databaseName, $tableName);
    }

    /**
     * @param FieldMappingInterface[] $fieldMappings
     */
    private function computeIndexFieldsFromFieldsMappings(array $fieldMappings): array
    {
        return array_map(
            static fn(FieldMappingInterface $fieldMapping)
                => new IndexField($fieldMapping->getIndexFieldName(), $fieldMapping->getIndexFieldType(), []),
            $fieldMappings
        );
    }
}
