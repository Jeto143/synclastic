<?php

namespace Jeto\Sqlastic\Mapping;

interface DataChangeProviderInterface
{
    /**
     * @return DataChange[]
     */
    public function fetchDataChanges(MappingInterface $mapping): array;

    public function markDataChangeAsProcessed(DataChange $dataChange): void;
}
