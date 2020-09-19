<?php

namespace Jeto\Sqlastic\Index\Updater;

use Jeto\Sqlastic\Mapping\MappingInterface;

interface IndexUpdaterInterface
{
    /**
     * @param mixed[] $identifiers
     */
    public function updateDocuments(MappingInterface $mapping, array $identifiers): void;
}
