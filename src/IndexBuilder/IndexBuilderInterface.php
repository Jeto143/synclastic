<?php

namespace Jeto\Elasticize\IndexBuilder;

interface IndexBuilderInterface
{
    public function buildIndex(string $databaseName, string $tableName): void;
}
