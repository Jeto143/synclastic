<?php

namespace Jeto\Sqlastic\Mapping\Database\FieldMapping;

interface FieldMappingInterface
{
    public function getIndexFieldName(): string;

    public function getIndexFieldType(): string;
}
