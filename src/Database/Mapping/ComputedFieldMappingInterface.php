<?php

namespace Jeto\Sqlastic\Database\Mapping;

interface ComputedFieldMappingInterface extends FieldMappingInterface
{
    public function getTargetDatabaseName(): string;

    public function getTargetTableName(): string;

    public function getValueQuery(): string;

    public function getOwnerIdQuery(): string;
}
