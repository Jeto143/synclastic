<?php

namespace Jeto\Elasticize\FieldMapping;

interface ComputedFieldMappingInterface extends FieldMappingInterface
{
    public function getTargetDatabaseName(): string;

    public function getTargetTableName(): string;

    public function getValueQuery(): string;

    public function getOwnerIdQuery(): string;
}
