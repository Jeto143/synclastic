<?php

namespace Jeto\Synclastic\Database\IndexDefinition;

use Jeto\Synclastic\Database\Introspector\DatabaseIntrospectorInterface;
use Jeto\Synclastic\Database\Mapping\BasicFieldMappingInterface;
use Jeto\Synclastic\Database\Mapping\MappingInterface;
use Jeto\Synclastic\Index\Definition\Definition as IndexDefinition;
use Jeto\Synclastic\Index\Definition\DefinitionInterface as IndexDefinitionInterface;
use Jeto\Synclastic\Index\Definition\Field as IndexField;

final class BasicIndexDefinitionFactory
{
    private DatabaseIntrospectorInterface $databaseIntrospector;

    public function __construct(DatabaseIntrospectorInterface $databaseIntrospector)
    {
        $this->databaseIntrospector = $databaseIntrospector;
    }

    public function create(string $indexName, MappingInterface $mapping): IndexDefinitionInterface
    {
        $identifierFieldName = $this->computeIdentifierFieldName($mapping->getDatabaseName(), $mapping->getTableName());
        $indexFields = $this->computeIndexFields($mapping->getBasicFieldsMappings());

        return new IndexDefinition($indexName, $indexFields, $identifierFieldName);
    }

    /**
     * @param BasicFieldMappingInterface[] $basicFieldMappings
     * @return IndexField[]
     */
    public function computeIndexFields(array $basicFieldMappings): array
    {
        return array_map(
            static fn(BasicFieldMappingInterface $basicFieldMapping)
            => new IndexField($basicFieldMapping->getIndexFieldName(), $basicFieldMapping->getIndexFieldType()),
            $basicFieldMappings
        );
    }

    private function computeIdentifierFieldName(string $databaseName, string $tableName): string
    {
        return $this->databaseIntrospector->fetchPrimaryKeyName($databaseName, $tableName);
    }
}
