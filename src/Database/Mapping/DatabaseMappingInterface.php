<?php

namespace Jeto\Synclastic\Database\Mapping;

interface DatabaseMappingInterface
{
    public function getDatabaseName(): string;

    public function getTableName(): string;

    public function getIndexName(): string;

    /**
     * @return BasicFieldMappingInterface[]
     */
    public function getBasicFieldsMappings(): array;

    /**
     * @return ComputedFieldMappingInterface[]
     */
    public function getComputedFieldsMappings(): array;

    /**
     * @return NestedArrayFieldMappingInterface[]
     */
    public function getNestedArrayFieldsMappings(): array;
}
