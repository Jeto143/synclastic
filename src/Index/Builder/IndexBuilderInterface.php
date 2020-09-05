<?php

namespace Jeto\Sqlastic\Index\Builder;

use Jeto\Sqlastic\Mapping\MappingInterface;

interface IndexBuilderInterface
{
    public function buildIndex(MappingInterface $mapping): void;
}
