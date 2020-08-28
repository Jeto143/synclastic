<?php

namespace Jeto\Elasticize\DatabaseInstrospector;

interface DatabaseIntrospectorInterface
{
    public function fetchPrimaryKeyName(string $databaseName, string $tableName): string;

    /**
     * @return string[]
     */
    public function fetchFieldsTypes(string $databaseName, string $tableName): array;
}
