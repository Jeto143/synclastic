<?php

namespace Jeto\Elasticize\FieldMapping;

interface BasicFieldMappingInterface extends FieldMappingInterface
{
    public function getDatabaseColumnName(): string;
}
