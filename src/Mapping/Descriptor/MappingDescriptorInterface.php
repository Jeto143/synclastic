<?php

namespace Jeto\Sqlastic\Mapping\Descriptor;

use Jeto\Sqlastic\Mapping\IndexDefinitionInterface;

interface MappingDescriptorInterface
{
    public function buildMapping(): IndexDefinitionInterface;
}
