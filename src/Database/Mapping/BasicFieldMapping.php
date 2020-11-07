<?php

namespace Jeto\Synclastic\Database\Mapping;

final class BasicFieldMapping implements BasicFieldMappingInterface
{
    private string $databaseColumnName;
    private string $databaseColumnType;
    private string $indexFieldType;

    public function __construct(string $databaseColumnName, string $databaseColumnType, ?string $indexFieldType = null)
    {
        $this->databaseColumnName = $databaseColumnName;
        $this->databaseColumnType = $databaseColumnType;
        $this->indexFieldType = $indexFieldType ?? $this->computeDefaultIndexFieldType($databaseColumnType);
    }

    public function getDatabaseColumnName(): string
    {
        return $this->databaseColumnName;
    }

    public function getDatabaseColumnType(): string
    {
        return $this->databaseColumnType;
    }

    public function getIndexFieldName(): string
    {
        return $this->databaseColumnName;
    }

    public function getIndexFieldType(): string
    {
        return $this->indexFieldType;
    }

    private function computeDefaultIndexFieldType(string $databaseColumnType): string
    {
        switch ($databaseColumnType) {
            case 'int':
            case 'tinyint':
            case 'integer':
                return 'integer';
            case 'date':
            case 'datetime':
            case 'timestamp':
            case 'timestamp without time zone':
                return 'date';
            case 'varchar':
            case 'character varying':
            default:
                return 'text';
        }
    }
}
