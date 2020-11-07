<?php

namespace Jeto\Synclastic\Configuration;

abstract class AbstractDatabaseFieldConfiguration
{
    private string $indexFieldName;

    private string $indexFieldType;

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
