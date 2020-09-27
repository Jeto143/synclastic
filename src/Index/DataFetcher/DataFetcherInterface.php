<?php

namespace Jeto\Synclastic\Index\DataFetcher;

use Jeto\Synclastic\Index\Definition\DefinitionInterface;

interface DataFetcherInterface
{
    /**
     * @param mixed[]|null $identifiers
     * @return iterable|mixed[][]
     */
    public function fetchSourceData(DefinitionInterface $indexDefinition, ?array $identifiers = null): iterable;
}
