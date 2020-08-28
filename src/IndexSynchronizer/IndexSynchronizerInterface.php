<?php

namespace Jeto\Elasticize\IndexSynchronizer;

interface IndexSynchronizerInterface
{
    public function synchronizeIndices(string $databaseName): void;
    public function populateIndices(string $databaseName, string $tableName): void;
}
