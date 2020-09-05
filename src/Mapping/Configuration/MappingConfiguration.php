<?php

namespace Jeto\Sqlastic\Mapping\Configuration;

use Jeto\Sqlastic\Mapping\MappingInterface;

final class MappingConfiguration implements MappingConfigurationInterface
{
    private string $dataChangeTableName;

    private string $triggerFormat;

    /** @var MappingInterface[] */
    private array $mappings;

    /**
     * @param string $dataChangeTableName
     * @param string $triggerFormat
     * @param MappingInterface[] $mappings
     */
    public function __construct(string $dataChangeTableName, string $triggerFormat, array $mappings)
    {
        $this->mappings = $mappings;
        $this->dataChangeTableName = $dataChangeTableName;
        $this->triggerFormat = $triggerFormat;
    }

    public function getDataChangeTableName(): string
    {
        return $this->dataChangeTableName;
    }

    public function getTriggerFormat(): string
    {
        return $this->triggerFormat;
    }

    /** @inheritDoc */
    public function getMappings(): array
    {
        return $this->mappings;
    }
}
