<?php

namespace Jeto\Sqlastic\Database\Introspection;

interface DatabaseIntrospectorInterface
{
    public function fetchPrimaryKeyName(string $databaseName, string $tableName): string;

    /**
     * @return string[]
     */
    public function fetchColumnsTypes(string $databaseName, string $tableName): array;
}
