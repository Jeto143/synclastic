<?php

namespace Jeto\Synclastic\Database\Mapping;

interface MappingInterface
{
    public function getDatabaseName(): string;

    public function getTableName(): string;

    /**
     * @return BasicFieldMappingInterface[]
     */
    public function getBasicFieldsMappings(): array;

    /**
     * @return ComputedFieldMappingInterface[]
     */
    public function getComputedFieldsMappings(): array;
}
