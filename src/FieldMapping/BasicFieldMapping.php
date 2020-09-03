<?php

namespace Jeto\Elasticize\FieldMapping;

final class BasicFieldMapping implements BasicFieldMappingInterface
{
    private string $databaseColumnName;
    private string $databaseColumnType;

    public function __construct(string $databaseColumnName, string $databaseColumnType)
    {
        $this->databaseColumnName = $databaseColumnName;
        $this->databaseColumnType = $databaseColumnType;
    }

    public function getDatabaseColumnName(): string
    {
        return $this->databaseColumnName;
    }

    public function getIndexFieldName(): string
    {
        return $this->databaseColumnName;
    }

    public function getIndexFieldType(): string
    {
        switch ($this->databaseColumnType) {
            case 'int':
            case 'tinyint':
                return 'integer';
            case 'varchar':
            default:
                return 'text';
        }
    }
}
