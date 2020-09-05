<?php

namespace Jeto\Sqlastic\Mapping\FieldMapping;

interface FieldMappingInterface
{
    public function getIndexFieldName(): string;

    public function getIndexFieldType(): string;
}
