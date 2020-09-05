<?php

namespace Jeto\Sqlastic\Database\Trigger;

use Jeto\Sqlastic\Mapping\MappingInterface;

interface TriggerCreatorInterface
{
    /**
     * @param MappingInterface[] $mappings
     */
    public function createDatabaseTriggers(array $mappings): void;
}
