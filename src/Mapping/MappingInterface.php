<?php

namespace Jeto\Sqlastic\Mapping;

use Jeto\Sqlastic\Mapping\FieldMapping\BasicFieldMappingInterface;
use Jeto\Sqlastic\Mapping\FieldMapping\ComputedFieldMappingInterface;

interface MappingInterface
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
}
