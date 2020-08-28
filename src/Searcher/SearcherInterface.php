<?php

namespace Jeto\Elasticize\Searcher;

interface SearcherInterface
{
    public function search(string $indexName, string $query): array;
}
