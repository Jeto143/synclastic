<?php

namespace Jeto\Sqlastic\Index\Populator;

use Jeto\Sqlastic\Mapping\MappingInterface;

interface IndexPopulatorInterface
{
    public function populateIndex(MappingInterface $mapping): void;
}
