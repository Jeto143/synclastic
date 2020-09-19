<?php

namespace Jeto\Sqlastic\Mapping\Database;

use Jeto\Sqlastic\Mapping\Database\FieldMapping\BasicFieldMappingInterface;
use Jeto\Sqlastic\Mapping\Database\FieldMapping\ComputedFieldMappingInterface;
use Jeto\Sqlastic\Mapping\MappingInterface;

interface DatabaseMappingInterface extends MappingInterface
{
    public function getDatabaseName(): string;

    public function getTableName(): string;

    /** @return BasicFieldMappingInterface[] */
    public function getBasicFieldsMappings(): array;

    /** @return ComputedFieldMappingInterface[] */
    public function getComputedFieldsMappings(): array;
}
