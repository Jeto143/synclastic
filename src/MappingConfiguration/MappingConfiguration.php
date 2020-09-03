<?php

namespace Jeto\Elasticize\MappingConfiguration;

use Jeto\Elasticize\Mapping\MappingInterface;

class MappingConfiguration implements MappingConfigurationInterface
{
    /** @var MappingInterface[] */
    private array $mappings;

    /**
     * @param MappingInterface[] $mappings
     */
    public function __construct(array $mappings)
    {
        $this->mappings = $mappings;
    }

    /**
     * @inheritDoc
     */
    public function getMappings(): array
    {
        return $this->mappings;
    }
}
