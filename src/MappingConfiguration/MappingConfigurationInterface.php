<?php

namespace Jeto\Elasticize\MappingConfiguration;

use Jeto\Elasticize\Mapping\MappingInterface;

interface MappingConfigurationInterface
{
    /**
     * @return MappingInterface[]
     */
    public function getMappings(): array;
}
