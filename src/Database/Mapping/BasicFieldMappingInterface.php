<?php

namespace Jeto\Sqlastic\Database\Mapping;

interface BasicFieldMappingInterface extends FieldMappingInterface
{
    public function getDatabaseColumnName(): string;
}
