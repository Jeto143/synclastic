<?php

namespace Jeto\Elasticize\DatabaseTriggerCreator;

use Jeto\Elasticize\Mapping\MappingInterface;

interface DatabaseTriggerCreatorInterface
{
    /**
     * @param MappingInterface[] $mappings
     */
    public function createDatabaseTriggers(array $mappings): void;
}
