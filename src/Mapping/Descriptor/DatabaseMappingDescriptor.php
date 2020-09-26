<?php

namespace Jeto\Sqlastic\Mapping\Descriptor;

use Jeto\Sqlastic\Database\ConnectionSettings;
use Jeto\Sqlastic\Mapping\Database\BasicDatabaseMapping;
use Jeto\Sqlastic\Mapping\IndexDefinitionInterface;

final class DatabaseMappingDescriptor implements MappingDescriptorInterface
{
    private ConnectionSettings $connectionSettings;

    private string $databaseName;

    private string $tableName;

    private string $fieldMappingStrategy;

    /** @var FieldDescriptor[] */
    private array $fieldsDescriptors;

    /** @var string[] */
    private array $ignoredFields;

    public function __construct(
        ConnectionSettings $connectionSettings,
        string $databaseName,
        string $tableName,
        string $fieldMappingStrategy,
        array $fieldsDescriptors,
        array $ignoredFields
    ) {
        $this->connectionSettings = $connectionSettings;
        $this->databaseName = $databaseName;
        $this->tableName = $tableName;
        $this->fieldMappingStrategy = $fieldMappingStrategy;
        $this->fieldsDescriptors = $fieldsDescriptors;
        $this->ignoredFields = $ignoredFields;
    }

    public function buildMapping(): IndexDefinitionInterface
    {
    }
}
