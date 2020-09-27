<?php

namespace Jeto\Synclastic\Index\Refiller;

use Jeto\Synclastic\Index\Definition\DefinitionInterface;
use Jeto\Synclastic\Index\Operation\Operation;

interface RefillerInterface
{
    /**
     * @return Operation[]
     */
    public function refillIndex(DefinitionInterface $indexDefinition): array;
}
