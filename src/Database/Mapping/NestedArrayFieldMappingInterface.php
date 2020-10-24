<?php

namespace Jeto\Synclastic\Database\Mapping;

interface NestedArrayFieldMappingInterface extends FieldMappingInterface
{
    public function getTargetDatabaseName(): string;

    public function getTargetTableName(): string;

    /**
     * @return BasicFieldMappingInterface[]
     */
    public function getBasicFieldsMappings(): array;

    public function getValuesQuery(): string;

    public function getOwnerIdQuery(): string;
}
