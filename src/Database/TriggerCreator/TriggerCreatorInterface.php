<?php

namespace Jeto\Sqlastic\Database\TriggerCreator;

use Jeto\Sqlastic\Mapping\Database\MappingInterface;

interface TriggerCreatorInterface
{
    /**
     * @param MappingInterface[] $mappings
     */
    public function createDatabaseTriggers(array $mappings): void;
}
