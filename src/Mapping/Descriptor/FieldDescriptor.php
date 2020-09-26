<?php

namespace Jeto\Sqlastic\Mapping\Descriptor;

final class FieldDescriptor
{
    private string $type;

    private string $databaseName;

    private string $tableName;

    private string $fieldMappingStrategy;

    /** @var FieldDescriptor[] */
    private array $fieldsDescriptors;

    /** @var string[] */
    private array $ignoredFields;

    public function __construct(
        string $type,
        string $databaseName,
        string $tableName,
        string $fieldMappingStrategy,
        array $fieldsDescriptors,
        array $ignoredFields
    ) {
        $this->type = $type;
        $this->databaseName = $databaseName;
        $this->tableName = $tableName;
        $this->fieldMappingStrategy = $fieldMappingStrategy;
        $this->fieldsDescriptors = $fieldsDescriptors;
        $this->ignoredFields = $ignoredFields;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getFieldMappingStrategy(): string
    {
        return $this->fieldMappingStrategy;
    }

    public function getFieldsDescriptors(): array
    {
        return $this->fieldsDescriptors;
    }

    public function getIgnoredFields(): array
    {
        return $this->ignoredFields;
    }
}
