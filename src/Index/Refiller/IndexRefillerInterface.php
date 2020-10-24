<?php

namespace Jeto\Synclastic\Index\Refiller;

use Jeto\Synclastic\Index\Definition\IndexDefinitionInterface;
use Jeto\Synclastic\Index\Operation\IndexOperation;

interface IndexRefillerInterface
{
    /**
     * @return IndexOperation[]
     */
    public function refillIndex(IndexDefinitionInterface $indexDefinition): array;
}
