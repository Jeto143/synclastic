<?php

namespace Jeto\Sqlastic\Mapping;

interface DataChangeProviderInterface
{
    public function fetchDataChanges(MappingInterface $mapping): iterable;

    public function markDataChangeAsProcessed(DataChange $dataChange): void;
}
