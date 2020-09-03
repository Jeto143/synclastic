<?php

namespace Jeto\Elasticize\IndexSynchronizer;

use Jeto\Elasticize\Mapping\MappingInterface;

interface IndexSynchronizerInterface
{
    public function synchronizeIndex(MappingInterface $mapping): void;

    public function clearAndSynchronizeIndex(MappingInterface $mapping): void;
}
