<?php

namespace Jeto\Synclastic\Search;

interface SearcherInterface
{
    public function search(string $indexName, string $query): array;
}
