<?php

namespace Jeto\Synclastic\Configuration;

use Jeto\Synclastic\Database\Introspector\DatabaseIntrospectorInterface;
use Jeto\Synclastic\Database\Mapping\FieldMappingInterface;

abstract class AbstractDatabaseFieldConfiguration
{
    protected string $indexFieldName;

    protected string $indexFieldType;

    public function __construct(string $indexFieldName, string $indexFieldType)
    {
        $this->indexFieldName = $indexFieldName;
        $this->indexFieldType = $indexFieldType;
    }

    public function getIndexFieldName(): string
    {
        return $this->indexFieldName;
    }

    public function getIndexFieldType(): string
    {
        return $this->indexFieldType;
    }
}
