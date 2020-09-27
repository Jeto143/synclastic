<?php

namespace Jeto\Synclastic\Index\Builder;

use Jeto\Synclastic\Index\Definition\DefinitionInterface;

interface BuilderInterface
{
    public function buildIndex(DefinitionInterface $indexDefinition): void;
}
