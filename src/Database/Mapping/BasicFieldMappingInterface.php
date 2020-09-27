<?php

namespace Jeto\Synclastic\Database\Mapping;

interface BasicFieldMappingInterface extends FieldMappingInterface
{
    public function getDatabaseColumnName(): string;
}
