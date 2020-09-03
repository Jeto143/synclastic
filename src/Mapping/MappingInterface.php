<?php

namespace Jeto\Elasticize\Mapping;

use Jeto\Elasticize\FieldMapping\BasicFieldMappingInterface;
use Jeto\Elasticize\FieldMapping\ComputedFieldMappingInterface;

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
