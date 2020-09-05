<?php

namespace Jeto\Sqlastic\Mapping\FieldMapping;

interface BasicFieldMappingInterface extends FieldMappingInterface
{
    public function getDatabaseColumnName(): string;
}
