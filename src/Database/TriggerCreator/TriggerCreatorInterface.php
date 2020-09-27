<?php

namespace Jeto\Sqlastic\Database\TriggerCreator;

use Jeto\Sqlastic\Database\Mapping\MappingInterface;

interface TriggerCreatorInterface
{
    /**
     * @param MappingInterface[] $mappings
     */
    public function createDatabaseTriggers(array $mappings): void;
}
