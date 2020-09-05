<?php

namespace Jeto\Sqlastic\Mapping\Configuration;

use Jeto\Sqlastic\Mapping\MappingInterface;

interface MappingConfigurationInterface
{
    public function getDataChangeTableName(): string;

    public function getTriggerFormat(): string;

    /**
     * @return MappingInterface[]
     */
    public function getMappings(): array;
}
