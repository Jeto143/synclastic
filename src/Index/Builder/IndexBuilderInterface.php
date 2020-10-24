<?php

namespace Jeto\Synclastic\Index\Builder;

use Jeto\Synclastic\Index\Definition\IndexDefinitionInterface;

interface IndexBuilderInterface
{
    public function buildIndex(IndexDefinitionInterface $indexDefinition): void;
}
