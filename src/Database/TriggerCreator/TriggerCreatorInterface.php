<?php

namespace Jeto\Synclastic\Database\TriggerCreator;

use Jeto\Synclastic\Database\Mapping\DatabaseMappingInterface;

interface TriggerCreatorInterface
{
    /**
     * @param DatabaseMappingInterface[] $mappings
     */
    public function createDatabaseTriggers(array $mappings): void;
}
