<?php

namespace Jeto\Sqlastic\Mapping\Configuration;

use Jeto\Sqlastic\Mapping\IndexDefinitionInterface;

interface MappingConfigurationInterface
{
    public function getDataChangeTableName(): string;

    public function getTriggerFormat(): string;

    /**
     * @return IndexDefinitionInterface[]
     */
    public function getMappings(): array;
}
