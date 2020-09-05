<?php

namespace Jeto\Sqlastic\Index\Synchronizer;

use Jeto\Sqlastic\Mapping\MappingInterface;

interface IndexSynchronizerInterface
{
    public function synchronizeIndex(MappingInterface $mapping): void;
}
