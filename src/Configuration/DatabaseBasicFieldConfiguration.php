<?php

namespace Jeto\Synclastic\Configuration;

use Jeto\Synclastic\Database\Mapping\BasicFieldMapping;
use Jeto\Synclastic\Database\Mapping\BasicFieldMappingInterface;

final class DatabaseBasicFieldConfiguration extends AbstractDatabaseFieldConfiguration
{
    private ?string $columnName;

    public function __construct(string $indexFieldName, string $indexFieldType, ?string $columnName = null)
    {
        parent::__construct($indexFieldName, $indexFieldType);
        $this->columnName = $columnName;
    }

    public function toBasicFieldMapping(array $columnsTypes): BasicFieldMappingInterface
    {
        $columnName = $this->columnName ?? $this->indexFieldName;

        return new BasicFieldMapping(
            $columnName,
            $columnsTypes[$columnName],
            $this->indexFieldType
        );
    }
}
