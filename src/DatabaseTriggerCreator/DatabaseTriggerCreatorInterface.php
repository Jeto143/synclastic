<?php

namespace Jeto\Elasticize\DatabaseTriggerCreator;

interface DatabaseTriggerCreatorInterface
{
    public function createDatabaseTriggers(string $databaseName, string $tableName): void;
}
