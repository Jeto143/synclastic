<?php

namespace Jeto\Synclastic\Database\TriggerCreator;

use Jeto\Synclastic\Database\Mapping\MappingInterface;

interface TriggerCreatorInterface
{
    /**
     * @param MappingInterface[] $mappings
     */
    public function createDatabaseTriggers(array $mappings): void;
}
