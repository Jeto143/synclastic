<?php

namespace Jeto\Elasticize\IndexBuilder;

use Jeto\Elasticize\Mapping\MappingInterface;

interface IndexBuilderInterface
{
    public function buildIndex(MappingInterface $mapping): void;
}
