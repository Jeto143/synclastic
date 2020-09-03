<?php

namespace Jeto\Elasticize\FieldMapping;

interface FieldMappingInterface
{
    public function getIndexFieldName(): string;

    public function getIndexFieldType(): string;
}
