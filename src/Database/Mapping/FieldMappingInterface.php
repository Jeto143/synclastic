<?php

namespace Jeto\Synclastic\Database\Mapping;

interface FieldMappingInterface
{
    public function getIndexFieldName(): string;

    public function getIndexFieldType(): string;
}
