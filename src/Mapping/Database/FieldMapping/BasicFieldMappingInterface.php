<?php

namespace Jeto\Sqlastic\Mapping\Database\FieldMapping;

interface BasicFieldMappingInterface extends FieldMappingInterface
{
    public function getDatabaseColumnName(): string;
}
