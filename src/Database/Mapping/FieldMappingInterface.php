<?php

namespace Jeto\Sqlastic\Database\Mapping;

interface FieldMappingInterface
{
    public function getIndexFieldName(): string;

    public function getIndexFieldType(): string;
}
