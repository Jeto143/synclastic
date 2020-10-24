<?php

namespace Jeto\Synclastic\Index\DataFetcher;

use Jeto\Synclastic\Index\Definition\IndexDefinitionInterface;

interface DataFetcherInterface
{
    /**
     * @param mixed[]|null $identifiers
     * @return iterable|mixed[][]
     */
    public function fetchSourceData(IndexDefinitionInterface $indexDefinition, ?array $identifiers = null): iterable;
}
